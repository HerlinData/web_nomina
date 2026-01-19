USE [BD_AMG_RRHH]
GO
/****** Object:  StoredProcedure [dbo].[SP_registrar_nuevo_contrato_ui_final]    Script Date: 17/01/2026 20:49:12 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
ALTER PROCEDURE [dbo].[SP_registrar_nuevo_contrato_ui_final]
(
    @id_persona            INT,
    @id_cargo              INT,
    @id_planilla           INT,
    @id_fp                 INT,
    @id_condicion          INT,
    @asignacion_familiar   BIT,
    @haber_basico          DECIMAL(10,2),
    @movilidad             DECIMAL(10,2),
    @id_banco              INT,
    @numero_cuenta         VARCHAR(100),
    @codigo_interbancario  VARCHAR(20),
    @id_moneda             INT,
    @inicio_contrato       DATE,           -- FINC
    @fin_contrato          DATE,
--    @fecha_renuncia        DATE = NULL,
    @periodo_prueba        BIT  = NULL,
    @id_centro_costo       INT,

    @resultado_validacion  INT             -- lo que trae el FRONT
)
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;

    DECLARE
        @id_contrato_nuevo   INT,
        @tipo_movimiento     VARCHAR(50),
        @lock_result         INT,
        @resultado_actual    INT,
        @mensaje             NVARCHAR(300),

        -- último contrato
        @inicio_ultimo       DATE,
        @fin_ultimo          DATE,
        @renuncia_ultimo     DATE;

    -- Validaciones básicas de rango
    IF @fin_contrato < @inicio_contrato
    BEGIN
        SELECT 0 AS ok,
               NULL AS id_contrato_nuevo,
               NULL AS tipo_movimiento,
               NULL AS resultado_validacion_actual,
               'Rango inválido: fin_contrato < inicio_contrato.' AS mensaje;
        RETURN;
    END

    BEGIN TRY
        BEGIN TRAN;

        /* 0) Si hay carga masiva en curso, bloquea el UI (candado global) */
        EXEC @lock_result = sp_getapplock
            @Resource     = N'RRHH_CONTRATOS_BULK',
            @LockMode     = 'Shared',
            @LockOwner    = 'Transaction',
            @LockTimeout  = 0;

        IF @lock_result < 0
        BEGIN
            SELECT 0 AS ok,
                   NULL AS id_contrato_nuevo,
                   NULL AS tipo_movimiento,
                   NULL AS resultado_validacion_actual,
                   'Carga masiva en progreso. Intente nuevamente.' AS mensaje;
            ROLLBACK;
            RETURN;
        END

        /* 1) Candado por persona (anti-concurrencia) */
        DECLARE @res_persona NVARCHAR(200) =
            N'RRHH_CONTRATO_PERSONA_' + CONVERT(NVARCHAR(20), @id_persona);

        EXEC @lock_result = sp_getapplock
            @Resource     = @res_persona,
            @LockMode     = 'Exclusive',
            @LockOwner    = 'Transaction',
            @LockTimeout  = 15000;

        IF @lock_result < 0
        BEGIN
            SELECT 0 AS ok,
                   NULL AS id_contrato_nuevo,
                   NULL AS tipo_movimiento,
                   NULL AS resultado_validacion_actual,
                   'No se pudo obtener bloqueo para la persona. Intente nuevamente.' AS mensaje;
            ROLLBACK;
            RETURN;
        END

        /* 2) Obtener el último contrato registrado (mayor inicio_contrato) */
        SELECT TOP (1)
            @inicio_ultimo   = c.inicio_contrato,
            @fin_ultimo      = c.fin_contrato,
            @renuncia_ultimo = c.fecha_renuncia
        FROM bronze.fact_contratos c
        WHERE c.id_persona = @id_persona
        ORDER BY c.inicio_contrato DESC;

        /* 3) Si no tiene contratos previos: lo tratamos como "Contrato nuevo" (15) */
        IF @inicio_ultimo IS NULL
        BEGIN
            SET @resultado_actual = 16;
        END
        ELSE
        BEGIN
            /* 4) Re-evaluación exacta de FINC (= @inicio_contrato) */
            IF @renuncia_ultimo IS NOT NULL
            BEGIN
                IF @inicio_contrato < @inicio_ultimo
                    SET @resultado_actual = 0;
                ELSE IF @inicio_contrato = @inicio_ultimo
                    SET @resultado_actual = 1;
                ELSE IF @inicio_contrato > @inicio_ultimo AND @inicio_contrato <= @renuncia_ultimo
                    SET @resultado_actual = 2;
                ELSE IF @inicio_contrato > @renuncia_ultimo AND @inicio_contrato <= @fin_ultimo
                    SET @resultado_actual = 3;
                ELSE
                    SET @resultado_actual = 4; -- @inicio_contrato > @fin_ultimo
            END
            ELSE
            BEGIN
                IF @inicio_contrato < @inicio_ultimo
                    SET @resultado_actual = 11;
                ELSE IF @inicio_contrato = @inicio_ultimo
                    SET @resultado_actual = 12;
                ELSE IF @inicio_contrato > @inicio_ultimo AND @inicio_contrato <= @fin_ultimo
                    SET @resultado_actual = 13;
                ELSE IF DATEDIFF(DAY, @fin_ultimo, @inicio_contrato) = 1
                    SET @resultado_actual = 14;
                ELSE
                    SET @resultado_actual = 15; -- @inicio_contrato > @fin_ultimo
            END
        END

        /* 5) En resultados que no permiten inserción: devolver mensaje y salir */
        IF @resultado_actual IN (0,1,2,11,12,13)
        BEGIN
            SET @mensaje =
                CASE @resultado_actual
                    WHEN 0  THEN N'Contrato no válido, ingresar un contrato reciente'
                    WHEN 1  THEN N'Contrato existente con misma fecha inicio'
                    WHEN 2  THEN N'Fecha ingresada errónea, revisar fecha_renuncia'
                    WHEN 11 THEN N'Contrato no válido, ingresar un contrato reciente'
                    WHEN 12 THEN N'Contrato existente con misma fecha inicio'
                    WHEN 13 THEN N'Fecha errónea, tiene contrato activo'
                END;

            SELECT 0 AS ok,
                   NULL AS id_contrato_nuevo,
                   NULL AS tipo_movimiento,
                   @resultado_actual AS resultado_validacion_actual,
                   @mensaje AS mensaje;

            ROLLBACK;
            RETURN;
        END

        /* 6) Verificar que el front no se haya quedado con una validación antigua */
        IF @resultado_actual <> @resultado_validacion
        BEGIN
            SELECT 0 AS ok,
                   NULL AS id_contrato_nuevo,
                   NULL AS tipo_movimiento,
                   @resultado_actual AS resultado_validacion_actual,
                   'Error inesperado, otro usuario agregó un registro diferente de la misma persona' AS mensaje;
            ROLLBACK;
            RETURN;
        END

        /* 7) Derivar tipo_movimiento (solo 3,4,14,15) */
        SET @tipo_movimiento =
            CASE @resultado_actual
                WHEN 3  THEN 'Contrato por baja'
                WHEN 4  THEN 'Contrato nuevo'
                WHEN 14 THEN 'Contrato por renovación'
                WHEN 15 THEN 'Contrato nuevo'
				WHEN 16 THEN 'Contrato inicial' -- PORSIACASO
            END;

        /* 8) Apagar movimientos activos anteriores (misma persona) */
        UPDATE m
        SET m.estado = 0
        FROM bronze.fact_contratos_movimientos m
        INNER JOIN bronze.fact_contratos c ON c.id_contrato = m.id_contrato
        WHERE c.id_persona = @id_persona
          AND m.estado = 1;

        /* 9) Apagar contratos activos anteriores (misma persona) */
        UPDATE c
        SET c.estado = 0
        FROM bronze.fact_contratos c
        WHERE c.id_persona = @id_persona
          AND c.estado = 1;

        /* 10) Insertar contrato nuevo (estado=1) */
        DECLARE @NewContrato TABLE (id_contrato INT);

        INSERT INTO bronze.fact_contratos
        (
            id_persona, id_cargo, id_planilla, id_fp, id_condicion,
            asignacion_familiar, haber_basico, movilidad,
            id_banco, numero_cuenta, codigo_interbancario,
            id_moneda, inicio_contrato, fin_contrato,
            periodo_prueba,
            id_centro_costo, estado
        )
        OUTPUT INSERTED.id_contrato INTO @NewContrato(id_contrato)
        VALUES
        (
            @id_persona, @id_cargo, @id_planilla, @id_fp, @id_condicion,
            @asignacion_familiar, @haber_basico, @movilidad,
            @id_banco, @numero_cuenta, @codigo_interbancario,
            @id_moneda, @inicio_contrato, @fin_contrato,
            @periodo_prueba,
            @id_centro_costo, 1
        );

        SELECT @id_contrato_nuevo = id_contrato FROM @NewContrato;

        /* 11) Insertar movimiento */
        INSERT INTO bronze.fact_contratos_movimientos
        (
            id_contrato, id_cargo, id_planilla, id_fp, id_condicion,
            asignacion_familiar, haber_basico, movilidad,
            id_banco, numero_cuenta, codigo_interbancario,
            id_moneda, inicio, fin,
            id_centro_costo, estado, tipo_movimiento
        )
        VALUES
        (
            @id_contrato_nuevo, @id_cargo, @id_planilla, @id_fp, @id_condicion,
            @asignacion_familiar, @haber_basico, @movilidad,
            @id_banco, @numero_cuenta, @codigo_interbancario,
            @id_moneda, @inicio_contrato, @fin_contrato,
            @id_centro_costo, 1, @tipo_movimiento
        );

        COMMIT;

        SELECT 1 AS ok,
               @id_contrato_nuevo AS id_contrato_nuevo,
               @tipo_movimiento AS tipo_movimiento,
               @resultado_actual AS resultado_validacion_actual,
               'Contrato y movimiento registrados correctamente' AS mensaje;

    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK;

        DECLARE @err INT = ERROR_NUMBER();

        IF @err IN (2601, 2627)
        BEGIN
            SELECT 0 AS ok,
                   NULL AS id_contrato_nuevo,
                   NULL AS tipo_movimiento,
                   NULL AS resultado_validacion_actual,
                   'Contrato duplicado: ya existe un contrato con el mismo inicio.' AS mensaje,
                   @err AS error_numero,
                   ERROR_MESSAGE() AS error_tecnico;
            RETURN;
        END

        ;THROW;
    END CATCH
END

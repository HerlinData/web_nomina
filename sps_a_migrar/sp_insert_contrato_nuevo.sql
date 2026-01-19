USE [BD_AMG_RRHH]
GO
/****** Object:  StoredProcedure [dbo].[SP_insert_contrato_movimientos]    Script Date: 17/01/2026 20:48:35 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
ALTER PROCEDURE [dbo].[SP_insert_contrato_movimientos]
 -- Parámetros básicos
 @id_persona INT,
 @id_cargo INT,
 @id_planilla INT,
 @id_fp INT,
 @id_condicion INT,
 @asignacion_familiar BIT,
 @haber_basico DECIMAL(10,2),
 @movilidad DECIMAL(10,2),
 @id_banco INT,
 @num_cuenta VARCHAR(100),
 @codigo_interbancario VARCHAR(20),
 @id_moneda INT,
 @fecha_inicial DATE,
 @fecha_final DATE,
 @id_centro_costo INT,  
 @estado BIT = 1
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @id_contrato_nuevo INT;
    
    BEGIN TRY
        BEGIN TRANSACTION;

        -- 1. INSERTAR EN FACT_CONTRATOS
        INSERT INTO [bronze].[fact_contratos]
        (
            id_persona,
            id_cargo,
            id_planilla,
            id_fp,
            id_condicion,
            asignacion_familiar,
            haber_basico,
            movilidad,
            id_banco,
            numero_cuenta,
            codigo_interbancario,
            id_moneda,
            inicio_contrato,
            fin_contrato,
            id_centro_costo,
            estado
        )
        VALUES
        (
            @id_persona,
            @id_cargo,
            @id_planilla,
            @id_fp,
            @id_condicion,
            @asignacion_familiar,
            @haber_basico,
            @movilidad,
            @id_banco,
            @num_cuenta,
            @codigo_interbancario,
            @id_moneda,
            @fecha_inicial,
            @fecha_final,
            @id_centro_costo,
            @estado
        );
        
        -- Capturar el ID del contrato recién creado
        SET @id_contrato_nuevo = SCOPE_IDENTITY();
        
        -- 2. INSERTAR AUTOMÁTICAMENTE EN FACT_CONTRATOS_MOVIMIENTOS
        INSERT INTO [bronze].[fact_contratos_movimientos]
        (
            id_contrato,
            id_cargo,
            id_planilla,
            id_fp,
            id_condicion,
            asignacion_familiar,
            haber_basico,
            movilidad,
            id_banco,
			numero_cuenta,
            codigo_interbancario,
            id_moneda,
            inicio,
            fin,
            id_centro_costo,
            estado,
            tipo_movimiento
        )
        VALUES
        (
            @id_contrato_nuevo,
            @id_cargo,
            @id_planilla,
            @id_fp,
            @id_condicion,
            @asignacion_familiar,
            @haber_basico,
            @movilidad,
            @id_banco,
			@num_cuenta,
            @codigo_interbancario,
            @id_moneda,
            @fecha_inicial,
            @fecha_final,
            @id_centro_costo,
            @estado,
            'Contrato inicial'
        );
        
        COMMIT TRANSACTION;
        
        SELECT
            1 AS ok,
            @id_contrato_nuevo AS id_contrato_nuevo,
            'Contrato y movimiento creados exitosamente' AS mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        DECLARE @ErrorNumber INT = ERROR_NUMBER();
        DECLARE @ErrorMessage NVARCHAR(4000) = ERROR_MESSAGE();

        -- Caso 1: duplicado por UNIQUE (id_persona, inicio_contrato, fin_contrato)
        IF @ErrorNumber IN (2601, 2627)
        BEGIN
            -- Si quieres retornar “formato front” (sin lanzar excepción):
            SELECT
                0 AS ok,
                NULL AS id_contrato_nuevo,
                'Ya existe un contrato para esta persona con la misma fecha de inicio y fin.' AS mensaje,
                @ErrorNumber AS error_numero
            RETURN;
        END

    END CATCH
END
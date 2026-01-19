USE [BD_AMG_RRHH]
GO
/****** Object:  StoredProcedure [dbo].[sp_evaluar_tipo_movimiento]    Script Date: 17/01/2026 20:47:28 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
ALTER PROCEDURE [dbo].[SP_evaluar_inicio_nuevo_contrato]
    @numero_documento      VARCHAR(20),
    @FINC                  DATE
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE
        @id_persona        INT,
        @inicio_ultimo     DATE,
        @fin_ultimo        DATE,
        @renuncia_ultimo   DATE,
        @resultado         INT,
        @mensaje           NVARCHAR(200);

    -- 1) Resolver id_persona por numero_documento
    SELECT @id_persona = p.id_persona
    FROM bronze.dim_persona p
    WHERE p.numero_documento = @numero_documento;

    --IF @id_persona IS NULL
    --BEGIN
    --    SELECT
    --        -1 AS resultado,
    --        N'Persona no encontrada para el número de documento ingresado' AS mensaje;
    --    RETURN;
    --END

    -- 2) Traer el último contrato (mayor inicio_contrato)
    SELECT TOP (1)
        @inicio_ultimo   = c.inicio_contrato,
        @fin_ultimo      = c.fin_contrato,
        @renuncia_ultimo = c.fecha_renuncia
    FROM bronze.fact_contratos c
    WHERE c.id_persona = @id_persona
    ORDER BY c.inicio_contrato DESC, c.id_contrato DESC;

    IF @inicio_ultimo IS NULL
    BEGIN
        SELECT
            16 AS resultado,
            N'La persona no tiene contratos previos registrados' AS mensaje;
        RETURN;
    END

 --LOGICA PARA EVALUACIÓN
    IF @renuncia_ultimo IS NOT NULL
    BEGIN
        IF @FINC < @inicio_ultimo
        BEGIN
            SET @resultado = 0;
            SET @mensaje   = N'Contrato no válido, ingresar un contrato reciente';
        END
        ELSE IF @FINC = @inicio_ultimo
        BEGIN
            SET @resultado = 1;
            SET @mensaje   = N'Contrato existente con misma fecha inicio';
        END
        ELSE IF @FINC > @inicio_ultimo AND @FINC <= @renuncia_ultimo
        BEGIN
            SET @resultado = 2;
            SET @mensaje   = N'Fecha ingresada errónea, revisar fecha_renuncia';
        END
        ELSE IF @FINC > @renuncia_ultimo AND @FINC <= @fin_ultimo
        BEGIN
            SET @resultado = 3;
            SET @mensaje   = N'Contrato por baja';
        END
        ELSE IF @FINC > @fin_ultimo
        BEGIN
            SET @resultado = 4;
            SET @mensaje   = N'Contrato nuevo';
        END
    END
    ELSE
    BEGIN
        IF @FINC < @inicio_ultimo
        BEGIN
            SET @resultado = 11;
            SET @mensaje   = N'Contrato no válido, ingresar un contrato reciente';
        END
        ELSE IF @FINC = @inicio_ultimo
        BEGIN
            SET @resultado = 12;
            SET @mensaje   = N'Contrato existente con misma fecha inicio';
        END
        ELSE IF @FINC > @inicio_ultimo AND @FINC <= @fin_ultimo
        BEGIN
            SET @resultado = 13;
            SET @mensaje   = N'Fecha errónea, tiene contrato activo';
        END
        ELSE IF DATEDIFF(DAY, @fin_ultimo, @FINC) = 1
        BEGIN
            -- Importante: esta condición debe ir ANTES que "FINC > fin_contrato"
            SET @resultado = 14;
            SET @mensaje   = N'Contrato por renovación';				
        END
        ELSE IF @FINC > @fin_ultimo
        BEGIN
            SET @resultado = 15;
            SET @mensaje   = N'Contrato nuevo';				
        END
    END

    SELECT @resultado AS resultado, @mensaje AS mensaje;
END

USE [BD_AMG_RRHH]
GO
/****** Object:  StoredProcedure [dbo].[SP_baja_insert]    Script Date: 17/01/2026 20:45:42 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
ALTER PROCEDURE [dbo].[SP_baja_insert]
(
    @id_contrato INT,
    @fecha_baja DATE,
    @motivo_baja VARCHAR(200),
    @aviso_con_15_dias BIT,
    @recomienda_reingreso BIT,
    @observacion VARCHAR(500) = NULL
)
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;

    DECLARE 
        @inicio_contrato DATE,
        @fin_contrato DATE,
        @fecha_renuncia DATE;

    BEGIN TRY
        BEGIN TRANSACTION;

        SELECT 
            @inicio_contrato = inicio_contrato,
            @fin_contrato    = fin_contrato,
            @fecha_renuncia  = fecha_renuncia
        FROM bronze.fact_contratos WITH (UPDLOCK, HOLDLOCK)
        WHERE id_contrato = @id_contrato;

        /* Ya existe baja */
        IF @fecha_renuncia IS NOT NULL
        BEGIN
            ROLLBACK TRANSACTION;

            SELECT 
                status     = 0,
                cod_status = -1,
                detalle    = 'El contrato ya posee una baja registrada';

            RETURN;
        END

        /* Fecha fuera de rango */
        IF @fecha_baja < @inicio_contrato
           OR (
                @fin_contrato IS NOT NULL 
                AND @fecha_baja > DATEADD(DAY, -1, @fin_contrato)
              )
        BEGIN
            ROLLBACK TRANSACTION;

            SELECT 
                status     = 0,
                cod_status = 0,
                detalle    = 'La fecha de baja está fuera del rango del contrato';

            RETURN;
        END

        /* Insert de baja */
        INSERT INTO bronze.fact_bajas
        (
            id_contrato,
            fecha_baja,
            motivo_baja,
            aviso_con_15_dias,
            recomienda_reingreso,
            observacion
        )
        VALUES
        (
            @id_contrato,
            @fecha_baja,
            @motivo_baja,
            @aviso_con_15_dias,
            @recomienda_reingreso,
            @observacion
        );

        COMMIT TRANSACTION;

        SELECT 
            status     = 1,
            cod_status = 1,
            detalle    = 'Baja registrada correctamente';

    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        SELECT 
            status     = 0,
            cod_status = -99,
            detalle    = 'Error, vuelva a intenta';
    END CATCH
END;

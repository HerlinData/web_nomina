USE [BD_AMG_RRHH]
GO
/****** Object:  StoredProcedure [dbo].[SP_baja_update]    Script Date: 17/01/2026 20:46:41 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
ALTER PROCEDURE [dbo].[SP_baja_update]
(
    @id_contrato INT,
    @nueva_fecha_baja DATE,
    @motivo_baja VARCHAR(200) = NULL,
    @aviso_con_15_dias BIT = NULL,
    @recomienda_reingreso BIT = NULL,
    @observacion VARCHAR(500) = NULL
)
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;

    DECLARE 
        @inicio_contrato DATE,
        @fin_contrato DATE;

    BEGIN TRY
        BEGIN TRANSACTION;

        /* Fechas del contrato */
        SELECT 
            @inicio_contrato = inicio_contrato,
            @fin_contrato    = fin_contrato
        FROM bronze.fact_contratos WITH (UPDLOCK, HOLDLOCK)
        WHERE id_contrato = @id_contrato;

        /* Validar rango de fecha */
        IF @nueva_fecha_baja < @inicio_contrato
           OR (
                @fin_contrato IS NOT NULL 
                AND @nueva_fecha_baja > DATEADD(DAY, -1, @fin_contrato)
              )
        BEGIN
            ROLLBACK TRANSACTION;

            SELECT 
                status     = 0,
                cod_status = 0,
                detalle    = 'La nueva fecha de baja está fuera del rango válido del contrato';

            RETURN;
        END

        /* Actualizar baja */
        UPDATE bronze.fact_bajas
        SET fecha_baja = @nueva_fecha_baja,
            motivo_baja = COALESCE(@motivo_baja, motivo_baja),
            aviso_con_15_dias = COALESCE(@aviso_con_15_dias, aviso_con_15_dias),
            recomienda_reingreso = COALESCE(@recomienda_reingreso, recomienda_reingreso),
            observacion = COALESCE(@observacion, observacion)
        WHERE id_contrato = @id_contrato;

        /* No existe baja */
        IF @@ROWCOUNT = 0
        BEGIN
            ROLLBACK TRANSACTION;

            SELECT 
                status     = 0,
                cod_status = -1,
                detalle    = 'No existe una baja registrada para el contrato';

            RETURN;
        END

        COMMIT TRANSACTION;

        SELECT 
            status     = 1,
            cod_status = 1,
            detalle    = 'Baja actualizada correctamente';

    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        SELECT 
            status     = 0,
            cod_status = -99,
            detalle    = 'Intente nuevamente, error inesperado';
    END CATCH
END;

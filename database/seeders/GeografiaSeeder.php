<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GeografiaSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = database_path('seeders/data/geografia.xlsx');

        if (!file_exists($filePath)) {
            $this->command->error("❌ Archivo no encontrado: {$filePath}");
            return;
        }

        $this->command->info("📂 Leyendo archivo: {$filePath}");

        try {
            $spreadsheet = IOFactory::load($filePath);

            // Limpiar datos
            $this->command->info("🗑️  Limpiando datos existentes...");
            $this->limpiarTabla('dim_distritos');
            $this->limpiarTabla('dim_provincias');
            $this->limpiarTabla('dim_departamentos');
            $this->limpiarTabla('dim_paises');

            // Cargar datos
            $this->seedPaises($spreadsheet);
            $this->seedDepartamentos($spreadsheet);
            $this->seedProvincias($spreadsheet);
            $this->seedDistritos($spreadsheet);

            $this->command->info("✅ Datos geográficos cargados exitosamente");

        } catch (\Exception $e) {
            $this->command->error("❌ Error: " . $e->getMessage());
        }
    }

    /**
     * Cargar países desde Excel
     */
    private function seedPaises($spreadsheet): void
    {
        $rows = $this->getRows($spreadsheet, 'dim_paises');
        if (!$rows) return;

        $this->command->info("🌍 Procesando países...");

        $batch = [];
        $now = now();

        foreach ($rows as $row) {
            $batch[] = [
                'id' => (int) $row['A'],
                'nombre' => trim($row['B']),
                'codigo_pais' => trim($row['C'] ?? ''),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($batch) {
            $values = [];
            foreach ($batch as $row) {
                $values[] = sprintf(
                    "(%d, '%s', '%s', '%s', '%s')",
                    $row['id'],
                    addslashes($row['nombre']),
                    addslashes($row['codigo_pais']),
                    $row['created_at'],
                    $row['updated_at']
                );
            }

            $sql = "SET IDENTITY_INSERT [bronze].[dim_paises] ON; ";
            $sql .= "INSERT INTO [bronze].[dim_paises] (id, nombre, codigo_pais, created_at, updated_at) VALUES ";
            $sql .= implode(', ', $values) . "; ";
            $sql .= "SET IDENTITY_INSERT [bronze].[dim_paises] OFF;";

            DB::unprepared($sql);
        }

    }

    /**
     * Cargar departamentos desde Excel
     */
    private function seedDepartamentos($spreadsheet): void
    {
        $rows = $this->getRows($spreadsheet, 'dim_departamentos');
        if (!$rows) return;

        $this->command->info("🗺️  Procesando departamentos...");

        $batch = [];
        $now = now();

        foreach ($rows as $row) {
            $batch[] = [
                'id' => (int) $row['A'],
                'pais_id' => (int) $row['B'],
                'nombre' => trim($row['C']),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 100) {
                $this->insertDepartamentos($batch);
                $batch = [];
            }
        }

        if ($batch) {
            $this->insertDepartamentos($batch);
        }
    }


    /**
     * Cargar provincias desde Excel
     */
    private function seedProvincias($spreadsheet): void
    {
        $rows = $this->getRows($spreadsheet, 'dim_provincias');
        if (!$rows) return;

        $this->command->info("🏘️  Procesando provincias...");

        $batch = [];
        $now = now();

        foreach ($rows as $row) {
            $batch[] = [
                'departamento_id' => (int) $row['A'],
                'nombre' => trim($row['B']),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 400) {
                DB::table('bronze.dim_provincias')->insert($batch);
                $batch = [];
            }
        }

        if ($batch) {
            DB::table('bronze.dim_provincias')->insert($batch);
        }
    }

    
    /**
     * Cargar distritos desde Excel
     */
    private function seedDistritos($spreadsheet): void
    {
        $rows = $this->getRows($spreadsheet, 'dim_distritos');
        if (!$rows) return;

        $this->command->info("🏘️  Procesando distritos...");

        $batch = [];
        $now = now();

        foreach ($rows as $row) {
            $batch[] = [
                'nombre' => trim($row['A']),
                'provincia_id' => (int) $row['B'],
                'departamento_id' => (int) $row['C'],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 300) {
                DB::table('bronze.dim_distritos')->insert($batch);
                $batch = [];
            }
        }

        if ($batch) {
            DB::table('bronze.dim_distritos')->insert($batch);
        }
    }

    /**
     * Obtener filas de una hoja Excel (sin encabezado, sin filas vacías)
     */
    private function getRows($spreadsheet, string $sheetName): ?array
    {
        if (!$spreadsheet->sheetNameExists($sheetName)) {
            $this->command->warn("⚠️  Hoja '{$sheetName}' no encontrada");
            return null;
        }

        $rows = $spreadsheet->getSheetByName($sheetName)->toArray(null, true, true, true);
        array_shift($rows); // Remover encabezado
        return array_filter($rows, fn($row) => !empty($row['A']));
    }

    /**
     * Insertar departamentos con IDENTITY_INSERT
     */
    private function insertDepartamentos(array $batch): void
    {
        $values = [];
        foreach ($batch as $row) {
            $values[] = sprintf(
                "(%d, %d, '%s', '%s', '%s')",
                $row['id'],
                $row['pais_id'],
                addslashes($row['nombre']),
                $row['created_at'],
                $row['updated_at']
            );
        }

        $sql = "SET IDENTITY_INSERT [bronze].[dim_departamentos] ON; ";
        $sql .= "INSERT INTO [bronze].[dim_departamentos] (id, pais_id, nombre, created_at, updated_at) VALUES ";
        $sql .= implode(', ', $values) . "; ";
        $sql .= "SET IDENTITY_INSERT [bronze].[dim_departamentos] OFF;";

        DB::unprepared($sql);
    }

    /**
     * Limpiar tabla y reiniciar IDENTITY
     */
    private function limpiarTabla(string $table): void
    {
        DB::table("bronze.{$table}")->delete();
        DB::statement("DBCC CHECKIDENT ('bronze.{$table}', RESEED, 0)");
    }
}

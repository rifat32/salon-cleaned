<?php

namespace App\Console\Commands;


    use Illuminate\Console\Command;
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Support\Facades\Artisan;
    use Illuminate\Support\Facades\Log;

    class CheckAndMigrate extends Command
    {
        protected $signature = 'check:migrate';
        protected $description = 'Check if tables exist before running migrations';

        public function handle()
        {
            // Get all migration files in the database/migrations directory
            $migrationFiles = glob(database_path('migrations/*.php'));

            // Open log file
            $logFile = storage_path('logs/migration.log');
            $logHandle = fopen($logFile, 'a');
            fwrite($logHandle, "Migration started at " . now() . "\n");
            foreach ($migrationFiles as $file) {
                $contents = file_get_contents($file);

                // Check for Schema::create statement
                if (preg_match('/Schema::create\(\'([^\']+)\'/', $contents, $matches)) {
                    $table = $matches[1];

                    // Check if the table already exists
                    if (Schema::hasTable($table)) {
                        $message = "Table {$table} already exists. Skipping migration.\n";
                        $this->info($message);
                    } else {
                        $message = "Migrating {$file}\n";
                        $this->info($message);
                        fwrite($logHandle, $message);

                        // Run the specific migration
                        Artisan::call('migrate', [
                            '--path' => str_replace(base_path(), '', $file),
                        ]);

                        // Log the successful migration
                        $message = "Migrated {$file} successfully.\n";
                        fwrite($logHandle, $message);
                    }
                }
                // Check for Schema::table statement
                else if (preg_match('/Schema::table\(\'([^\']+)\'/', $contents, $matches)) {
                    $table = $matches[1];

                    // Extract column definitions
                    preg_match_all('/->(string|integer|text|boolean|date|timestamps)\(\'([^\']+)\'/', $contents, $columnMatches);
                    $columns = $columnMatches[2];

                    // Check if any of the columns already exist
                    $skipMigration = false;
                    // foreach ($columns as $column) {
                    //     if (Schema::hasColumn($table, $column)) {
                    //         $message = "Column {$column} already exists in table {$table}. Skipping migration.\n";
                    //         $this->info($message);
                    //         $skipMigration = true;
                    //         break;
                    //     }
                    // }

                    if (!$skipMigration) {
                        $message = "Modifying table {$table} with migration {$file}\n";
                        $this->info($message);
                        fwrite($logHandle, $message);

                        try {
                            // Run the specific migration
                            Artisan::call('migrate', [
                                '--path' => str_replace(base_path(), '', $file),
                            ]);

                            // Log the successful migration
                            $message = "Migrated {$file} successfully.\n";
                            fwrite($logHandle, $message);
                        } catch (\Exception $e) {
                            // Log the error message
                            $errorMessage = "Migration failed for {$file}. Error: " . $e->getMessage() . "\n";
                            $this->error($errorMessage);
                            fwrite($logHandle, $errorMessage);
                        }
                    } else {
                        $message = "Column already exists in {$file}. Skipping.\n";
                        $this->warn($message);
                        fwrite($logHandle, $message);
                    }

                }
                // No create or table statement found
                else {
                    $message = "No create or table statement found in {$file}. Skipping.\n";
                    $this->warn($message);
                    fwrite($logHandle, $message);
                }
            }

            // Close log file
            fwrite($logHandle, "Migration finished at " . now() . "\n\n");
            fclose($logHandle);
        }
}

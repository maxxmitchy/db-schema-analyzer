<?php

namespace App\Console\Commands;

use App\Exceptions\SchemaAnalysisException;
use App\Services\SchemaAnalyzerService;
use App\Services\SchemaOptimizerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class AnalyzeDbSchemaCommand extends Command
{
    protected $signature = 'db:analyze
                            {--connection= : The database connection to analyze}
                            {--optimize : Optimize the schema after analysis}
                            {--strategy= : The analysis strategy to use (basic or advanced)}';

    protected $description = 'Analyze and optionally optimize the database schema';

    public function __construct(
        private SchemaAnalyzerService $analyzerService,
        private SchemaOptimizerService $optimizerService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $connection = $this->option('connection') ?? Config::get('database.default');
        $strategy = $this->option('strategy') ?? Config::get('schema-analyzer.default_strategy');

        Config::set('schema-analyzer.default_strategy', $strategy);

        $this->info("Analyzing database schema for connection: {$connection} using {$strategy} strategy");

        try {
            $analysis = $this->analyzerService->analyze($connection);
            $this->displayAnalysis($analysis);

            if ($this->option('optimize')) {
                $this->info('Optimizing database schema...');
                $optimizations = $this->optimizerService->optimize($analysis);
                $this->displayOptimizations($optimizations);
            }

            return Command::SUCCESS;
        } catch (SchemaAnalysisException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function displayAnalysis(array $analysis): void
    {
        foreach ($analysis as $tableName => $tableAnalysis) {
            $this->info("\nTable: {$tableName}");
            $this->table(['Metric', 'Value'], [
                ['Column Count', $tableAnalysis['columnCount']],
                ['Index Count', $tableAnalysis['indexCount']],
                ['Foreign Key Count', $tableAnalysis['foreignKeyCount']],
            ]);

            if (!empty($tableAnalysis['potentialIssues'])) {
                $this->warn('Potential Issues:');
                foreach ($tableAnalysis['potentialIssues'] as $issue) {
                    $this->line("- {$issue}");
                }
            }

            if (isset($tableAnalysis['queryPerformance'])) {
                $this->info('Query  Performance:');
                $this->line("Average Execution Time: {$tableAnalysis['queryPerformance']['averageExecutionTime']} seconds");
                if (!empty($tableAnalysis['queryPerformance']['slowQueries'])) {
                    $this->warn('Slow Queries:');
                    foreach ($tableAnalysis['queryPerformance']['slowQueries'] as $query) {
                        $this->line("- Execution Time: {$query['execution_time']} seconds");
                        $this->line("  Query: {$query['query']}");
                    }
                }
            }
        }
    }

    private function displayOptimizations(array $optimizations): void
    {
        foreach ($optimizations as $tableName => $tableOptimizations) {
            $this->info("\nOptimizations for Table: {$tableName}");

            foreach ($tableOptimizations as $category => $suggestion) {
                if (!empty($suggestion)) {
                    $this->line("\n{$category}:");

                    $this->line("- {$suggestion}");
                }
            }
        }
    }
}

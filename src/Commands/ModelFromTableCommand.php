<?php

namespace Laracademy\Generators\Commands;

use DB;
use Schema;
use Illuminate\Support\Str;
use Illuminate\Console\Command;

class ModelFromTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:modelfromtable
                            {--table= : a single table or a list of tables separated by a comma (,)}
                            {--connection= : database connection to use, leave off and it will use the .env connection}
                            {--debug= : turns on debugging}
                            {--folder= : by default models are stored in app, but you can change that}
                            {--namespace= : by default the namespace that will be applied to all models is App}
                            {--singular : class name and class file name singular or plural}
                            {--all= : run for all tables}
                            {--overwrite= : overwrite model(s) if exists}
                            {--timestamps= : whether to timestamp or not}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate models for the given tables based on their columns';

    public $fieldsDocBlock;
    public $fieldsFillable;
    public $fieldsHidden;
    public $fieldsCast;
    public $fieldsDate;
    public $columns;

    public $debug;
    public $options;
    private $delimiter;

    public $databaseConnection;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->options = [
            'connection' => '',
            'namespace'  => '',
            'table'      => '',
            'folder'     => $this->getModelPath(),
            'debug'      => false,
            'all'        => false,
            'singular'   => false,
            'overwrite'  => false
        ];

        $this->delimiter = config('modelfromtable.delimiter', ', ');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->doComment('Starting Model Generate Command', true);
        $this->getOptions();

        $tables = [];
        $path = $this->options['folder'];
        $overwrite = $this->getOption('overwrite', false);
        $modelStub = file_get_contents($this->getStub());

        // can we run?
        if (strlen($this->options['table']) <= 0 && $this->options['all'] == false) {
            $this->error('No --table specified or --all');

            return;
        }

        // figure out if we need to create a folder or not
        if($this->options['folder'] != $this->getModelPath()) {
            if(! is_dir($this->options['folder'])) {
                mkdir($this->options['folder']);
            }
        }

        // figure out if it is all tables
        if ($this->options['all']) {
            $tables = $this->getAllTables();
        } else {
            $tables = explode(',', $this->options['table']);
        }

        // cycle through each table
        foreach ($tables as $table) {
            // grab a fresh copy of our stub
            $stub = $modelStub;

            // generate the file name for the model based on the table name
            $filename = Str::studly($table);

            if ($this->options['singular']){
                $filename = Str::singular($filename);
            }

            $fullPath = "$path/$filename.php";

            if (!$overwrite and file_exists($fullPath)) {
                $this->doComment("Skipping file: $filename.php");
                continue;
            }

            $this->doComment("Generating file: $filename.php");

            // gather information on it
            $model = [
                'table'     => $table,
                'fillable'  => $this->getSchema($table),
                'guardable' => [],
                'hidden'    => [],
                'casts'     => [],
            ];

            // fix these up
            $columns = $this->describeTable($table);

            // use a collection
            $this->columns = collect();

            foreach ($columns as $col) {
                $this->columns->push([
                    'field' => $col->Field,
                    'type'  => $col->Type,
                ]);
            }

            // reset fields
            $this->resetFields();

            // replace the class name
            $stub = $this->replaceClassName($stub, $table);

            // replace the fillable
            $stub = $this->replaceModuleInformation($stub, $model);

            // figure out the connection
            $stub = $this->replaceConnection($stub, $this->options['connection']);

            // writing stub out
            $this->doComment('Writing model: '.$fullPath, true);
            file_put_contents($fullPath, $stub);
        }

        $this->info('Complete');
    }

    public function getSchema($tableName)
    {
        $this->doComment('Retrieving table definition for: '.$tableName);

        if (strlen($this->options['connection']) <= 0) {
            return Schema::getColumnListing($tableName);
        } else {
            return Schema::connection($this->options['connection'])->getColumnListing($tableName);
        }
    }

    public function describeTable($tableName)
    {
        $this->doComment('Retrieving column information for : '.$tableName);

        if (strlen($this->options['connection']) <= 0) {
            return DB::select(DB::raw("describe `{$tableName}`"));
        } else {
            return DB::connection($this->options['connection'])->select(DB::raw("describe `{$tableName}`"));
        }
    }

    /**
     * replaces the class name in the stub.
     *
     * @param string $stub      stub content
     * @param string $tableName the name of the table to make as the class
     *
     * @return string stub content
     */
    public function replaceClassName($stub, $tableName)
    {
        return str_replace('{{class}}', $this->options['singular'] ? Str::singular(Str::studly($tableName)): Str::studly($tableName), $stub);
    }

    /**
     * replaces the module information.
     *
     * @param string $stub             stub content
     * @param array  $modelInformation array (key => value)
     *
     * @return string stub content
     */
    public function replaceModuleInformation($stub, $modelInformation)
    {
        // replace table
        $stub = str_replace('{{table}}', $modelInformation['table'], $stub);

        $primaryKey = config('modelfromtable.primaryKey', 'id');

        // allow config to apply a lamba to obtain non-ordinary primary key name
        if (is_callable($primaryKey)) {
            $primaryKey = $primaryKey($modelInformation['table']);
        }

        // replace fillable
        $this->fieldsDocBlock = '';
        $this->fieldsHidden = '';
        $this->fieldsFillable = '';
        $this->fieldsCast = '';
        foreach ($modelInformation['fillable'] as $field) {
            // fillable and hidden
            if ($field != $primaryKey) {
                $this->interpolate($this->fieldsFillable, "'$field'");

                $fieldsFiltered = $this->columns->where('field', $field);
                if ($fieldsFiltered) {
                    // check type
                    $type = strtolower($fieldsFiltered->first()['type']);
                    $type = preg_replace("/\s.*$/", '', $type);
                    preg_match_all("/^(\w*)\((?:(\d+)(?:,(\d+))*)\)/", $type, $matches);

                    $columnType = isset($matches[1][0]) ? $matches[1][0] : $type;
                    $columnLength = isset($matches[2][0]) ? $matches[2][0] : '';

                    $generateDocLine = function (string $type, string $field) { return str_pad("\n * @property {$type}", 25, ' ') . "$$field";};

                    switch ($columnType) {
                        case 'int':
                        case 'tinyint':
                        case 'boolean':
                        case 'bool':
                            $castType = ($columnLength == 1) ? 'boolean' : 'int';

                            $this->interpolate($this->fieldsDocBlock, $generateDocLine($castType, $field), "");
                            $this->interpolate($this->fieldsCast, "'$field' => '$castType'");
                            break;
                        case 'varchar':
                        case 'text':
                        case 'tinytext':
                        case 'mediumtext':
                        case 'longtext':
                            $this->interpolate($this->fieldsDocBlock, $generateDocLine('string', $field), "");
                            $this->interpolate($this->fieldsCast, "'$field' => 'string'");
                            break;
                        case 'float':
                        case 'double':
                            $this->interpolate($this->fieldsDocBlock, $generateDocLine('float', $field), "");
                            $this->interpolate($this->fieldsCast, "'$field' => '$columnType'");
                            break;
                        case 'timestamp':
                            $this->interpolate($this->fieldsDocBlock, $generateDocLine('int', $field), "");
                            $this->interpolate($this->fieldsCast, "'$field' => '$columnType'");
                            $this->interpolate($this->fieldsDate, "'$field'");
                            break;
                        case 'datetime':
                            $this->interpolate($this->fieldsDocBlock, $generateDocLine('DateTime', $field), "");
                            $this->interpolate($this->fieldsCast, "'$field' => '$columnType'");
                            $this->interpolate($this->fieldsDate, "'$field'");
                            break;
                        case 'date':
                            $this->interpolate($this->fieldsDocBlock, $generateDocLine('Date', $field), "");
                            $this->interpolate($this->fieldsCast, "'$field' => '$columnType'");
                            $this->interpolate($this->fieldsDate, "'$field'");
                            break;
                    }
                }
            } else {
                if ($field != $primaryKey && $field != 'created_at' && $field != 'updated_at') {
                    $this->interpolate($this->fieldsHidden, "'$field'");
                }
            }
        }

        $timestamps = ($this->getOption('timestamps', false, true)) ? 'true' : 'false';

        // replace in stub
        $stub = str_replace('{{docblock}}', $this->fieldsDocBlock, $stub);
        $stub = str_replace('{{primaryKey}}', $primaryKey, $stub);
        $stub = str_replace('{{fillable}}', $this->fieldsFillable, $stub);
        $stub = str_replace('{{hidden}}', $this->fieldsHidden, $stub);
        $stub = str_replace('{{casts}}', $this->fieldsCast, $stub);
        $stub = str_replace('{{dates}}', $this->fieldsDate, $stub);
        $stub = str_replace('{{timestamps}}', $timestamps, $stub);
        $stub = str_replace('{{modelnamespace}}', $this->options['namespace'], $stub);

        return $stub;
    }

    private function interpolate(string &$string, string $add, $delimiter = null)
    {
        $delimiter = $delimiter ?? $this->delimiter;
        $string .= (strlen($string) > 0 ? $delimiter : '').$add;
    }

    public function replaceConnection($stub, $database)
    {
        $replacementString = '/**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = \''.$database.'\';'."\n\n";

        if (strlen($database) <= 0) {
            $stub = str_replace('{{connection}}', '', $stub);
        } else {
            $stub = str_replace('{{connection}}', $replacementString, $stub);
        }

        return $stub;
    }

    /**
     * returns the stub to use to generate the class.
     */
    public function getStub()
    {
        $this->doComment('loading model stub');

        return __DIR__.'/../stubs/model.stub';
    }

    /**
     * returns all the options that the user specified.
     */
    public function getOptions()
    {
        // debug
        $this->options['debug'] = $this->getOption('debug', false, true);

        // connection
        $this->options['connection'] = $this->getOption('connection', '');

        // folder
        $this->options['folder'] = $this->getOption('folder', '');

        // namespace
        $this->options['namespace'] = $this->getOption('namespace', '');

        // namespace with possible folder
        // if there is no folder specified and no namespace
        if(! $this->options['folder'] && ! $this->options['namespace']) {
            // assume default APP
            $this->options['namespace'] = 'App';
        } else {
            // if we have a namespace, use it first
            if($this->options['namespace']) {
                $this->options['namespace'] = str_replace('/', '\\', $this->options['namespace']);
            } else {
                if($folder = $this->options['folder']) {
                    $this->options['namespace'] = str_replace('/', '\\', $folder);
                }
            }
        }

        // finish setting up folder
        $this->options['folder'] = ($this->options['folder']) ? base_path($this->options['folder']) : $this->getModelPath();
        // trim trailing slashes
        $this->options['folder'] = rtrim($this->options['folder'], '/');

        // all tables
        $this->options['all'] = $this->getOption('all', false, true);

        // single or list of tables
        $this->options['table'] = $this->getOption('table', '');

        // class name and class file name singular/plural
        $this->options['singular'] = $this->getOption('singular', false, true);
    }

    /**
     * returns single option with priority being user input, then user config, then default
     */
    private function getOption(string $key, $default = null, bool $isBool = false)
    {
        if ($isBool) {
            $return = ($this->option($key))
                ? filter_var($this->option($key), FILTER_VALIDATE_BOOLEAN)
                : config("modelfromtable.{$key}", $default);
        } else {
            $return = $this->options[$key] = $this->option($key) ?? config("modelfromtable.{$key}", $default);
        }

        return $return;
    }

    private function getModelPath()
    {
        return (app()->version() > '8')? app()->path('Models') : app()->path();
    }

    /**
     * will add a comment to the screen if debug is on, or is over-ridden.
     */
    public function doComment($text, $overrideDebug = false)
    {
        if ($this->options['debug'] || $overrideDebug) {
            $this->comment($text);
        }
    }

    /**
     * will return an array of all table names.
     */
    public function getAllTables()
    {
        $tables = [];
        $whitelist = config('modelfromtable.whitelist', []);
        $blacklist = config('modelfromtable.blacklist', []);

        if (strlen($this->options['connection']) <= 0) {
            $tables = collect(DB::select(DB::raw("show full tables where Table_Type = 'BASE TABLE'")))->flatten();
        } else {
            $tables = collect(DB::connection($this->options['connection'])->select(DB::raw("show full tables where Table_Type = 'BASE TABLE'")))->flatten();
        }

        $tables = $tables->map(function ($value, $key) {
            return collect($value)->flatten()[0];
        })->reject(function ($value, $key) use ($blacklist) {
            foreach($blacklist as $reject) {
                if (fnmatch($reject, $value)) {
                    return true;
                }
            }
        })->filter(function ($value, $key) use ($whitelist) {
            if (!$whitelist) {
                return true;
            }
            foreach($whitelist as $accept) {
                if (fnmatch($accept, $value)) {
                    return true;
                }
            }
        });

        return $tables;
    }

    /**
     * reset all variables to be filled again when using multiple
     */
    public function resetFields()
    {
        $this->fieldsDocBlock = '';
        $this->fieldsFillable = '';
        $this->fieldsHidden   = '';
        $this->fieldsCast     = '';
        $this->fieldsDate     = '';
    }
}

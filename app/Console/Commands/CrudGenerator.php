<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CrudGenerator extends Command
{
    protected $signature = 'make:crud {name} {--fields=}';
    protected $description = 'Create CRUD operations for the specified model';

    protected $fieldTypes = [
        'string' => 'text',
        'text' => 'textarea',
        'integer' => 'number',
        'boolean' => 'checkbox',
        'date' => 'date',
        'datetime' => 'datetime-local',
        'time' => 'time',
        'email' => 'email',
        'password' => 'password',
        'enum' => 'select',
        'file' => 'file',
        'wysiwyg' => 'wysiwyg'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $name = $this->argument('name');
        $fieldsOption = $this->option('fields');

        $fields = $this->parseFields($fieldsOption);

        $this->generateModel($name, $fields);
        $this->generateController($name, $fields);
        $this->generateViews($name, $fields);
        $this->generateRoutes($name);

        $this->info('CRUD generated successfully!');
    }

    protected function parseFields($fieldsOption)
    {
        $fields = [];
        if ($fieldsOption) {
            $fieldPairs = explode(',', $fieldsOption);
            foreach ($fieldPairs as $pair) {
                list($name, $type) = explode(':', $pair);
                $validations = [];

                // Parse validations if they exist
                if (strpos($type, '|')) {
                    list($type, $validationString) = explode('|', $type);
                    $validations = explode('&', $validationString);
                }

                $fields[$name] = [
                    'type' => $type,
                    'validations' => $validations
                ];
            }
        }
        return $fields;
    }

    protected function generateModel($name, $fields)
    {
        $modelTemplate = str_replace(
            [
                '{{modelName}}',
                '{{fillable}}'
            ],
            [
                $name,
                $this->getFillableFields($fields)
            ],
            $this->getStub('Model')
        );

        if (!file_exists($path = app_path('/Models'))) {
            mkdir($path, 0777, true);
        }

        file_put_contents(app_path("/Models/{$name}.php"), $modelTemplate);
    }

    protected function generateController($name, $fields)
    {
        $controllerTemplate = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePluralLowerCase}}',
                '{{modelNameSingularLowerCase}}',
                '{{validationRules}}'
            ],
            [
                $name,
                strtolower(Str::plural($name)),
                strtolower($name),
                $this->getValidationRules($fields)
            ],
            $this->getStub('Controller')
        );

        if (!file_exists($path = app_path('/Http/Controllers'))) {
            mkdir($path, 0777, true);
        }

        file_put_contents(app_path("/Http/Controllers/{$name}Controller.php"), $controllerTemplate);
    }

    protected function generateViews($name, $fields)
    {
        $viewsPath = resource_path('views/' . strtolower(Str::plural($name)));

        if (!file_exists($viewsPath)) {
            mkdir($viewsPath, 0777, true);
        }

        // Generate index view
        $indexTemplate = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePluralLowerCase}}',
                '{{fields}}',
            ],
            [
                $name,
                strtolower(Str::plural($name)),
                $this->getFieldsForIndex($fields)
            ],
            $this->getStub('views/index')
        );
        file_put_contents($viewsPath . '/index.blade.php', $indexTemplate);

        // Generate create view
        $createTemplate = str_replace(
            [
                '{{modelName}}',
                '{{modelNameSingularLowerCase}}',
                '{{fields}}',
            ],
            [
                $name,
                strtolower($name),
                $this->getFieldsForForm($fields)
            ],
            $this->getStub('views/create')
        );
        file_put_contents($viewsPath . '/create.blade.php', $createTemplate);

        // Generate edit view
        $editTemplate = str_replace(
            [
                '{{modelName}}',
                '{{modelNameSingularLowerCase}}',
                '{{fields}}',
            ],
            [
                $name,
                strtolower($name),
                $this->getFieldsForForm($fields, true)
            ],
            $this->getStub('views/edit')
        );
        file_put_contents($viewsPath . '/edit.blade.php', $editTemplate);
    }

    protected function generateRoutes($name)
    {
        $routeFile = base_path('routes/web.php');
        $routes = "\nRoute::resource('" . strtolower(Str::plural($name)) . "', {$name}Controller::class);";

        File::append($routeFile, $routes);
    }

    protected function getStub($type)
    {
        return file_get_contents(resource_path("stubs/$type.stub"));
    }

    protected function getFillableFields($fields)
    {
        return "'" . implode("', '", array_keys($fields)) . "'";
    }

    protected function getValidationRules($fields)
    {
        $rules = [];
        foreach ($fields as $field => $options) {
            if (!empty($options['validations'])) {
                $rules[] = "'{$field}' => '" . implode('|', $options['validations']) . "'";
            }
        }
        return implode(",\n            ", $rules);
    }

    protected function getFieldsForIndex($fields)
    {
        $tableFields = '';
        foreach ($fields as $field => $options) {
            $tableFields .= "<th>{$this->formatFieldName($field)}</th>\n";
        }
        return $tableFields;
    }

    protected function getFieldsForForm($fields, $isEdit = false)
    {
        $formFields = '';

        foreach ($fields as $field => $options) {
            $type = $options['type'];
            $validations = $options['validations'];

            $value = $isEdit ? "{{ \$item->{$field} }}" : "{{ old('{$field}') }}";
            $required = in_array('required', $validations) ? 'required' : '';

            switch ($type) {
                case 'wysiwyg':
                    $formFields .= $this->generateWysiwygField($field, $value, $required);
                    break;
                case 'file':
                    $formFields .= $this->generateFileField($field, $required);
                    break;
                case 'select':
                    $formFields .= $this->generateSelectField($field, $value, $required);
                    break;
                case 'textarea':
                    $formFields .= $this->generateTextareaField($field, $value, $required);
                    break;
                default:
                    $formFields .= $this->generateInputField($field, $type, $value, $required);
            }
        }

        return $formFields;
    }

    protected function formatFieldName($field)
    {
        return ucwords(str_replace('_', ' ', $field));
    }

    protected function generateInputField($field, $type, $value, $required)
    {
        return "
<div class=\"form-group\">
    <label for=\"{$field}\">{$this->formatFieldName($field)}</label>
    <input type=\"{$type}\" name=\"{$field}\" id=\"{$field}\" class=\"form-control @error('{$field}') is-invalid @enderror\" value=\"{$value}\" {$required}>
    @error('{$field}')
        <span class=\"invalid-feedback\" role=\"alert\">
            <strong>{{ \$message }}</strong>
        </span>
    @enderror
</div>";
    }

    protected function generateTextareaField($field, $value, $required)
    {
        return "
<div class=\"form-group\">
    <label for=\"{$field}\">{$this->formatFieldName($field)}</label>
    <textarea name=\"{$field}\" id=\"{$field}\" class=\"form-control @error('{$field}') is-invalid @enderror\" rows=\"3\" {$required}>{$value}</textarea>
    @error('{$field}')
        <span class=\"invalid-feedback\" role=\"alert\">
            <strong>{{ \$message }}</strong>
        </span>
    @enderror
</div>";
    }

    protected function generateSelectField($field, $value, $required)
    {
        return "
<div class=\"form-group\">
    <label for=\"{$field}\">{$this->formatFieldName($field)}</label>
    <select name=\"{$field}\" id=\"{$field}\" class=\"form-control @error('{$field}') is-invalid @enderror\" {$required}>
        <option value=\"\">Select {$this->formatFieldName($field)}</option>
        {{-- Add your options here --}}
    </select>
    @error('{$field}')
        <span class=\"invalid-feedback\" role=\"alert\">
            <strong>{{ \$message }}</strong>
        </span>
    @enderror
</div>";
    }

    protected function generateFileField($field, $required)
    {
        return "
<div class=\"form-group\">
    <label for=\"{$field}\">{$this->formatFieldName($field)}</label>
    <div class=\"custom-file\">
        <input type=\"file\" name=\"{$field}\" id=\"{$field}\" class=\"custom-file-input @error('{$field}') is-invalid @enderror\" {$required}>
        <label class=\"custom-file-label\" for=\"{$field}\">Choose file</label>
    </div>
    @error('{$field}')
        <span class=\"invalid-feedback\" role=\"alert\">
            <strong>{{ \$message }}</strong>
        </span>
    @enderror
</div>";
    }

    protected function generateWysiwygField($field, $value, $required)
    {
        return "
<div class=\"form-group\">
    <label for=\"{$field}\">{$this->formatFieldName($field)}</label>
    <textarea name=\"{$field}\" id=\"{$field}\" class=\"form-control wysiwyg @error('{$field}') is-invalid @enderror\" {$required}>{$value}</textarea>
    @error('{$field}')
        <span class=\"invalid-feedback\" role=\"alert\">
            <strong>{{ \$message }}</strong>
        </span>
    @enderror
</div>
@push('scripts')
<script>
    ClassicEditor
        .create(document.querySelector('#{$field}'))
        .catch(error => {
            console.error(error);
        });
</script>
@endpush";
    }
}

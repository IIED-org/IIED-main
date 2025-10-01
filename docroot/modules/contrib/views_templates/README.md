# Views Template 8.x

## Use

This module is intended to be used by other modules to offer Views templates.
Modules can implement ViewsTemplateBuilder plugins to provide Views templates

## Plugin in base classes

### ViewsBuilderBase
Extend this class to make View template pragmatically

### ViewsDuplicateBuilderBase
Extend this class to make a View Template from a *.yml Views Template file.

Views Templates should put in the 'views_templates' in the root directory
of the module.

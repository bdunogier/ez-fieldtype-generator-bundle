# eZ FieldType generator

## Status

This is a proof-of-concept/prototype. It generates a `Type` and a `Value` class, as well as a `fieldtypes.yml` that registers the
fieldtype, but the class name, namespace, etc are still wrong.

## Installation

Since the package isn't registered on packagist yet, you need to add a VCS entry to \ composer.json`:

Require the ezobject/wrapperbundle package into your composer.json file :
```
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/bdunogier/ez-fieldtype-generator-bundle.git" }
    ],
    "require": {
        "bdunogier": "dev-master"
    }
}
```

Add `BDEzFieldTypeGeneratorBundle` to `EzPublishKernel.php`:

```
$bundles = array(
    // ...
    new BD\EzFieldTypeGeneratorBundle\BDEzFieldTypeGeneratorBundle(),
);
```

## Usage

```
php ezpublish/console generate:ez:fieldtype
```

When asked for the name of the bundle, use an existing bundle. If you don't have one for it, generate one using
`generate:bundle` first.

The script will then ask for the fieldtype's name, that is really the fieldtype's identifier.

Confirm generation, and the files will be written to the bundle:

```
$ php app/console generate:ez:fieldtype

  Welcome to the eZ FieldType generator

Your FieldType must be created in a bundle.

If you don't have a bundle for it, please create one using generate:bundle first
Target bundle name: BDTestBundle

A FieldType requires a unique identifier.
Identifiers can contain alphanumeric characters as well as underscores

FieldType name: bdtest

  Summary before generation


You are going to generate a "bdtest" FieldType in the "BDTestBundle" bundle.

Do you confirm generation [yes]?
Generating the FieldType code: OK
```

# Laracademy Generators

[![Latest Stable Version](https://poser.pugx.org/laracademy/generators/v/stable)](https://packagist.org/packages/laracademy/generators) [![Total Downloads](https://poser.pugx.org/laracademy/generators/downloads)](https://packagist.org/packages/laracademy/generators) [![Latest Unstable Version](https://poser.pugx.org/laracademy/generators/v/unstable)](https://packagist.org/packages/laracademy/generators) [![License](https://poser.pugx.org/laracademy/generators/license)](https://packagist.org/packages/laracademy/generators)

**Laracademy Generators** - is a tool set that helps speed up the development process of a Laravel application.

**Author(s):**
* [Laracademy](https://laracademy.co) ([@laracademy](http://twitter.com/laracademy), michael@laracademy.co)

## Requirements

1. PHP 5.6+
2. Laravel 6.*
3. MySQL *

** For Laravel 5.* please use the version 1.5

## Usage

### Step 1: Install through Composer

```
composer require "laracademy/generators" --dev
```

### Step 2: Artisan Command
Now that we have added the generator to our project the last thing to do is run Laravel's Arisan command

```
php artisan
```

You will see the following in the list

```
generate:modelfromtable
```

## Commands

### generate:modelfromtable

This command will read your database table and generate a model based on that table structure. The fillable fields, casts, dates and even namespacing will be filled in automatically.

You can use this command to generate a single table, multiple tables or all of your tables at once.

This command comes with a bunch of different options, please see below for each parameter

* --table=
  * This parameter if filled in will generate a model for the given table.
   * You can also pass in a list of tables using comma separated values.
* --all
  * If this flag is present, then the table command will be ignored.
   * This will generate a model for **all** tables found in your database.
   * _please note that this command will only ignore the `migrations` table and no model will be generate for it_
* --connection=
  * by default if this option is omitted then the generate will use the default connection found in `config/database.php`
  * To specify a connection ensure that it exists in your `config/database.php` first.
* --folder=
  * by default all models are store in your _app/_ directory. If you wish to store them in another place you can provide the relative path from your base laravel application.
  * please see examples for more information
* --namespace=
  * by default all models will have the namespace of App
  * you can change the namespace by adding this option
* --debug
  * this shows some more information while running
* --singular
  * this will create a singular titled model

## Examples

### Generating a single table

```
php artisan generate:modelfromtable --table=users
```

### Generating a multiple tables

```
php artisan generate:modelfromtable --table=users,posts
```

### Generating all tables

```
php artisan generate:modelfromtable --all
```

### Changing to another connection found in `database.php` and generating models for all tables

```
php artisan generate:modelfromtable --connection=spark --all
```

### Changing the folder where to /app/Models

```
php artisan generate:modelfromtable --table=user --folder=app\Models
```

## License
ModelGen is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

### Bug Reporting and Feature Requests
Please add as many details as possible regarding submission of issues and feature requests

### Disclaimer
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

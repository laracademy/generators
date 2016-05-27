# Model Generator

[![Latest Stable Version](https://poser.pugx.org/laracademy/generators/v/stable)](https://packagist.org/packages/laracademy/generators) [![Total Downloads](https://poser.pugx.org/laracademy/generators/downloads)](https://packagist.org/packages/laracademy/generators) [![Latest Unstable Version](https://poser.pugx.org/laracademy/generators/v/unstable)](https://packagist.org/packages/laracademy/model-generator) [![License](https://poser.pugx.org/laracademy/model-generator/license)](https://packagist.org/packages/laracademy/generators)

**Laracademy Generators** - is a set of generators that will help speed up your development.

**Author(s):**
* [Laracademy](https://laracademy.co) ([@laracademy](http://twitter.com/laracademy), michael@laracademy.co)

## Requirements

1. PHP 5.6+
3. Laravel 5.2+

Will read your current table structure and generate a model will the filled in fields automatically.

You can generate a single table model, or multiple at once.

## Usage

### Step 1: Install through Composer

```
composer require "laracademy/model-generator"
```

### Step 2: Add the Service Provider
The easiest method is to add the following into your `config/app.php` file

```php
Laracademy\ModelGenerator\ModelGeneratorServiceProvider::class
```

Depending on your set up you may want to only use these providers for development, so you don't update your `production` servers. Instead, add the provider in `app/Providers/AppServiceProvider.php' like so

```php
public function register()
{
    if($this->app->environment() == 'local') {
        $this->app->register('\Laracademy\ModelGenerator\ModelGeneratorServiceProvider');
    }
```

### Artisan
Now that we have added the generator to our project the last thing to do is run Laravel's Arisan command

```
php artisan
```

You will see the following in the list

```
generate:modelfromtable
```

The command comes with a bunch of different options and they are listed below

 * --table=
  * this can either be a single table, or a list of tables separated by a comma
 * --all
  * this will ignore any tables that you have added and generate a full list of tables within your database to generate models for
  * please note that this command will only ignore the `migrations` table and no model will be generate for it
 * --connection=
  *
 * --debug
  * this shows some more information while running

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

## License
ModelGen is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

### Bug Reporting and Feature Requests
Please add as many details as possible regarding submission of issues and feature requests

### Disclaimer
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
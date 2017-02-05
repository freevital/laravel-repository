# Laravel Repositories

[![Latest Stable Version](https://poser.pugx.org/freevital/laravel-repository/v/stable)](https://packagist.org/packages/freevital/laravel-repository)
[![Total Downloads](https://poser.pugx.org/freevital/laravel-repository/downloads)](https://packagist.org/packages/freevital/laravel-repository)
[![Monthly Downloads](https://poser.pugx.org/freevital/laravel-repository/d/monthly)](https://packagist.org/packages/freevital/laravel-repository)
[![License](https://poser.pugx.org/freevital/laravel-repository/license)](https://packagist.org/packages/freevital/laravel-repository)

Laravel Repositories to abstract a database layer.

## Installation

Run the following command to install the latest version:

```bash
composer require "freevital/laravel-repository"
```

## Usage

### Create a Repository

Your repository class must extend `Freevital\Repository\Eloquent\BaseRepository` abstract class and implement method `model()` which returns model's class name.

```php
namespace App\Repositories\Eloquent;

use Freevital\Repository\Eloquent\BaseRepository;   

class PostRepository extends BaseRepository
{
    /**
     * Specify Model class name.
     *
     * @return string
     */
    public function model()
    {
        return "App\Post";
    }
}
```

### Use Repository in the Controller

```php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repositories\Criteria\BySlugCriteria;
use App\Repositories\Criteria\WithCommentsCriteria;
use App\Repositories\Eloquent\PostRepository;

class PostController extends Controller
{
    /**
     * @var PostRepository
     */
    protected $postRepository;

    /**
     * @param PostRepository $postRepository
     */
    public function __construct(PostRepository $postRepository)
    {
        $this->postRepository = $postRepository;
    }

    /**
     * Get all posts.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $posts = $this->postRepository->all();
    
        return \Response::json(compact('posts'));
    }
}
```

### Create a Repository Criteria

Optionally you may create a separate Criteria class to apply specific query conditions. Your Criteria class must implement `Freevital\Repository\Contracts\CriteriaContract` interface.

```php
namespace App\Repositories\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Freevital\Repository\Contracts\CriteriaContract;
use Freevital\Repository\Contracts\RepositoryContract;

class BySlugCriteria implements CriteriaContract
{
    /**
     * @var string
     */
    protected $slug;

    /**
     * @param string $slug
     */
    public function __construct($slug)
    {
        $this->slug = $slug;
    }

    /**
     * Apply criteria in query repository.
     *
     * @param Builder            $query
     * @param RepositoryContract $repository
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Builder $query, RepositoryContract $repository)
    {
        return $query->where('slug', $this->slug);
    }
}
```

### Use Repository Criteria in the Controller

You may use multiple criteria in the repository.

```php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repositories\Criteria\BySlugCriteria;
use App\Repositories\Criteria\WithCommentsCriteria;
use App\Repositories\Eloquent\PostRepository;

class PostController extends Controller
{
     /**
     * Get a post by slug.
     *
     * @param string $slug
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($slug)
    {
        $post = $this->postRepository
            ->pushCriteria(new WithCommentsCriteria())
            ->pushCriteria(new BySlugCriteria($slug))
            ->first();
    
        return \Response::json(compact('post'));
    }
}
```

## Criteria Macros


If you would like to extend the repository functionality with custom common scope (ex. ActiveCriteria), you may use BaseRepository's macro method. For example, from a service provider's boot method:

```php
namespace App\Providers;

use Freevital\Repository\Criteria\ActiveCriteria;
use Freevital\Repository\Eloquent\BaseRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryMacroServiceProvider extends ServiceProvider
{
    /**
     * Register the application's repository macros.
     *
     * @return void
     */
    public function boot()
    {
        BaseRepository::macro('active', function (BaseRepository $repository) {
            $repository->pushCriteria(new ActiveCriteria());
        });
    }
}
```

The macro function accepts a name as its first argument, and a Closure as its second. The macro's Closure will be executed when calling the macro name from  any Repository instance:

```php
$this->postRepository->active()->all();
```

## Available Methods

#### Freevital\Repository\Contracts\RepositoryContract

```php
paginate($limit = null, $columns = ['*'], $method = 'paginate')
simplePaginate($limit = null, $columns = ['*'])
all($columns = ['*'])
lists($column, $key = null)
find($id, $columns = ['*'])
first($columns = ['*'])
findByField($field, $value, $columns = ['*'])
findWhere(array $where, $columns = ['*'])
findWhereIn($field, array $values, $columns = ['*'])
findWhereNotIn($field, array $values, $columns = ['*'])
count()
create(array $attributes)
update(array $attributes, $id)
updateOrCreate(array $attributes, array $values = [])
updateActiveStatus($status, int $id)
delete($id)
forceDelete($id)
deleteWhere(array $where)
forceDeleteWhere(array $where)
has($relation)
with($relations)
whereHas($relation, $closure)
orderBy($column, $direction = 'asc')
visible(array $fields)
hidden(array $fields)
scopeQuery(\Closure $scope)
resetScope()
```

#### Freevital\Repository\Contracts\RepositoryCriteriaContract

```php
pushCriteria($criteria)
popCriteria($criteria)
getCriteria()
getByCriteria(CriteriaContract $criteria)
skipCriteria($status = true)
resetCriteria()
```

#### Freevital\Repository\Contracts\CriteriaContract

```php
apply(Builder $query, RepositoryContract $repository)
```

## Example usage

Get all entities:

```php
$this->postRepository->all();

// Fetch the specific columns
$this->postRepository->all(['id', 'title']);
```

Entity pagination:

```php
$this->postRepository->paginate(20);
```

Get an entity by id:

```php
$this->postRepository->find($id);
```

Get first entity:

```php
$this->postRepository->pushCriteria(...)->first();
```

Get entities count:

```php
$this->postRepository->pushCriteria(...)->count();
```

Create new entity:

```php
$this->postRepository->create(Input::all());
```

Update an entity by id:

```php
$this->postRepository->update(Input::all(), $id);
```

Delete or force delete an entity by id:

```php
$this->postRepository->delete($id);
$this->postRepository->forceDelete($id);
```

## Credits

This package in mainly based on package by [@andersao](https://github.com/andersao/l5-repository).

## License

The contents of this repository is released under the MIT license.
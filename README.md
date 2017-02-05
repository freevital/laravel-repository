# Laravel Repositories

[![Latest Stable Version](https://poser.pugx.org/freevital/laravel-repository/v/stable)](https://packagist.org/packages/freevital/laravel-repository)
[![Total Downloads](https://poser.pugx.org/freevital/laravel-repository/downloads)](https://packagist.org/packages/freevital/laravel-repository)
[![Monthly Downloads](https://poser.pugx.org/freevital/laravel-repository/d/monthly)](https://packagist.org/packages/freevital/laravel-repository)
[![License](https://poser.pugx.org/freevital/laravel-repository/license)](https://packagist.org/packages/freevital/laravel-repository)

Laravel Repositories to abstract the database layer.

##Installation

Run the following command to install the latest version

```bash
composer require "freevital/laravel-repository"
```

##Usage

###Create a Repository

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

###Use the repository in the controller

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

###Create a Repository Criteria

Optionally you may create a separate Criteria class to apply specific query conditions. Your Criteria class must implement `Freevital\Repository\Contracts\CriteriaContract` interface.

```php
namespace App\Repositories\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Freevital\Repository\Contracts\CriteriaContract;
use Freevital\Repository\Contracts\RepositoryContract;

class BySlugCriteria implements CriteriaContract
{
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
        return $query->with('comments.users');
    }
}
```

You may TODO

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
    protected $title;

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
        return $query->where('name', $this->slug);
    }
}
```

###Use Repository Criteria in the controller

You may use multiple criteria in a repository.

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
            ->pushCriteria(new WithCommentsCriteria($slug))
            ->pushCriteria(new BySlugCriteria($slug))
            ->first();
    
        return \Response::json(compact('post'));
    }
}
```

##Available Methods

####Freevital\Repository\Contracts\RepositoryContract

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

####Freevital\Repository\Contracts\RepositoryCriteriaContract

```php
pushCriteria($criteria)
popCriteria($criteria)
getCriteria()
getByCriteria(CriteriaContract $criteria)
skipCriteria($status = true)
resetCriteria()
```

####Freevital\Repository\Contracts\CriteriaContract

```php
apply(Builder $query, RepositoryContract $repository)
```

##Credits

This package in mainly based on package by [@andersao](https://github.com/andersao/l5-repository).

##License

The contents of this repository is released under the MIT license.
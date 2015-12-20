# Avalon Database

The Avalon database package makes use of the Doctrine [Database Abstraction Layer][1],
it is essentially a small wrapper around it that provides a simple "Model" system.

This is _not_ an ORM.

## Models

Models are a representation of a database row, you can select, update and delete
rows with models.

## Selecting rows into models

When using a model to fetch data, it will automatically be placed into a model
for you.

```php
class Article extends Avalon\Database\Model {}

$articles = Article::where('is_published = :is_published')
    ->andWhere('user_id = :user_id')
    ->setParameter('is_published', 1)
    ->setParameter('user_id', 5)
    // Normally with Doctrine and PDO you would call `->execute()`,
    // you could do that here, but it would return the statement.
    //
    // If you simply call `fetchAll()` you will get an array of `Article` models.
    ->fetchAll();

foreach ($articles as $article) {
    echo $article->title;
}
```

[1]: http://www.doctrine-project.org/projects/dbal.html

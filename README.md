# missing-tested-routes

### Video/blog is out of date... 
### Uses controller actions with `@action` nowinstead of named routes using `@route`

This script returns a list of laravel routes that have yet to be tested. Here's a 1 minute video showing just what this script does.

[![missing laravel route tests](https://img.youtube.com/vi/VJO3ZbUf1UI/0.jpg)](https://www.youtube.com/watch?v=VJO3ZbUf1UI)

# Install and Usage

To use first copy the 2 files in this repository (exclude the README) into `~/.bin` and make sure that `~/.bin` is in your `$PATH` Next *inside the root of your Laravel application* run the command 

`$ missing-test-routes` 

The response you get will vary depending on how many untested routes you have currently. For example, you should see some output that looks like this:

```
    /**
     * @action BooksController@edit
     * @route books.edit
     */
    public function test_books_edit()
    {
        $this->markTestIncomplete('This test is incomplete');
        $response = $this->call('GET', "/books/{books}/edit");
        $this->assertEquals(200, $response->status());
    }


    /**
     * @action BooksController@update
     * @route books.update
     */
    public function test_books_update()
    {
        $this->markTestIncomplete('This test is incomplete');
        $data = [];
        $response = $this->call('PUT', "/books/{books}", $data);
        $this->assertEquals(302, $response->status());
    }
```

# How it works

To mark routes as tested you simply need to add an annotation in the docblock of the test method. That is how the script knows if a route has been marked as tested. The script compares your laravel apps route list with the list of annotations it finds in your test suite. You don't have to put all tests in a single file. It scans the entire test/ directory for the `@action` annotations. Here is a sample route annotation.

```
@action BooksController@edit
```

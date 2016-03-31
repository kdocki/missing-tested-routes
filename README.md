# missing-tested-routes
Gets a list of missing tested laravel named routes

To use first copy these files into `~/.bin` and make sure that `~/.bin` is in your `$PATH`

Next you just run the command 

`$ missing-test-routes` 

inside of your root project of your Laravel application. The response you get will vary depending on how many untested routes you have currently.

To mark named routes as tested you simply need to add an annotation in the docblock of the test method. That is what the

```
 @route ...
```

does so it knows which named routes have been tested and which have not.

# Batch API for Wordpress

This is a developer only plugin to be used in plugin development.

If you are familiar with the Batch API in Drupal, this provides similar functionality 
for Wordpress. Allows you to process large data sets without worrying about timeouts.

I've used this to handle large migrations into Wordpress. Imagine having a CSV file 
of 200+ blog posts. Processing each row creates a new post in Wordpress, downloads 
image assets, etc. This would not work for me without this batch functionality.

## How to Use

Install and activate the plugin.

Add a hidden field with the batch id to your form.

`<input type="hidden" name="batchapi_id" value="<?php print \BatchAPI\BatchAPI::generateUniqueId(); ?>">`

Store your operations into an array. Once you've stored all of your operations, call
the execute method.

```
public function process_import( $rows ) {

    // Get the batch id from the post data.
    $batch_id = $_POST['batchapi_id'];
    
    // Set a redirect url.
    $redirect_url = admin_url( 'tools.php?page=your-page' );
    
    // Store operations to eventually pass to the Batch API.
    $operations = array();
    
    foreach ( $rows as $row ) {
        $operations[] = array(
            'function' => 'Namespace\Importer::import_row',
            'args' => array( $row )
        );
    }
    
    // Call the execute method.
    \BatchAPI\BatchAPI::execute( $batch_id, $operations, $redirect_url );
}
```

## Alternatives
You could use a scheduled task to handle your processing. Would be nice if that 
scheduled task notified you when the processing was complete.

## Disclaimer

Use at your own risk. Be sure to back up your data before running this. While I've 
used it with great success, I have not tested all the scenarios.

## License

MIT © [JC Hamill](http://jchamill.com)
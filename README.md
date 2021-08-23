# Flarum Mithril2Html

Uses Chrome Puppeteer via [Spatie Browsershot](https://github.com/spatie/browsershot) to render Mithril components as static HTML.

Follow Browsershot instructions to setup Node and Headless Chrome.

This is intended for use with emails or other offline content generation.

It's probably not a good idea to use this outside of a queue because of the delays it introduces.

## Usage

In your extension's `extend.php`, call the setup extender before registering any asset:

The extender can be called by multiple extensions without issues.
It won't do anything once already registered.

```php
return [
    new \ClarkWinkelmann\Mithril2Html\Setup(),
    
    // Your other extenders
];
```

Create a new page just like you would a normal Flarum page:

```js
import Page from 'flarum/common/components/Page';

class HelloWorld extends Page {
    view() {
        return <p>Hello World</p>;
    }
}

app.initializers.add('demo', function () {
    app.routes.helloWorld = {
        path: '/hello-world',
        component: HelloWorld,
    };
});
```

To save up space in the `forum` bundle or to prevent conflicts, you can add your page only to the `mithril2html` frontend.
You will need to update your webpack config to add an additional entry file, see this package's `webpack.config.js` for an example.

If you created a separate bundle (not `forum`), register it using Flarum's `Frontend` extender:

```php
    (new Frontend('mithril2html'))
        ->js(__DIR__ . '/js/dist/mithril2html.js'),
```

You can then use the `Renderer` class to render the component:

```php
$component = new ClarkWinkelmann\Mithril2Html\AnonymousComponent('hello-world');
echo resolve(ClarkWinkelmann\Mithril2Html\Renderer::class)->render($component);
// <p>Hello World</p>
```

Alternatively, you can use the blade directive directly:

```php
@mithril2html(new ClarkWinkelmann\Mithril2Html\AnonymousComponent('hello-world'))
```

You can configure additional options using a component class.
The class must implement `ClarkWinkelmann\Mithril2Html\ComponentInterface`.
`AnonymousComponent` is a simple class that allows customizing all parameters without creating additional classes.

The parameters customizable through a component class are:

- `route`: The Mithril route name without leading slash.
- `preload`: An API route to preload through the API Client. With leading slash.
- `actor`: An actor to use for the request. Defaults to guest.
- `selector`: A CSS selector targeting the HTML to return. That element's innerHTML will be returned. If the selector can't be found, an exception will be thrown.

Using a custom component class helps keep things clean when preloading is necessary:

```php
class InvoiceComponent implements ComponentInterface {
    protected $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function route(): string
    {
        return 'invoice';
    }

    public function preload(): ?string
    {
        return '/invoices/' . $this->invoice->id;
    }

    public function actor(): ?User
    {
        return $this->invoice->user;
    }

    public function selector(): ?string
    {
        return '#content';
    }
}
```

```
<p>Below is a summary of your invoice:</p>

@mithril2html(new InvoiceComponent($invoice))
```

## Known issues

At the moment, passing an actor will authenticate the base request and preloaded `apiDocument`, but not any additional API request the component will make after page load.

## Tests

The integration tests are a bit special because they require a working webserver that can be accessed by Chrome.

Run `composer test:server` before running the tests to start the PHP development server on port `8080`.
The server is configured with a router script that takes care of routing back to the integration tmp folder.

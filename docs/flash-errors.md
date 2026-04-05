# Flash & validation errors

## Flash messages

Flash a value into `page.props.flash` for the next Inertia render. The value survives redirects (POST → 303 → GET) via the session and is consumed automatically — it will not appear on subsequent renders.

```php
#[Route('/profile', methods: ['POST'])]
public function update(Request $request, Inertia $inertia): Response
{
    // ... handle update

    $inertia->flash('success', 'Profile updated successfully.');
    return new RedirectResponse('/profile');
}
```

On the next `GET /profile` render, `page.props.flash` will contain:

```json
{ "success": "Profile updated successfully." }
```

Multiple flash keys can be set before the redirect:

```php
$inertia->flash('success', 'Saved.');
$inertia->flash('count', 3);
```

> When no session is available (stateless routes), flash data is kept in memory and only available within the same request lifecycle.

---

## Validation errors

Persist validation errors to be auto-injected into `page.props.errors` on the next render. Follows the same PRG (Post/Redirect/Get) pattern as flash messages.

```php
#[Route('/register', methods: ['POST'])]
public function register(Request $request, Inertia $inertia): Response
{
    $errors = $this->validate($request);

    if (!empty($errors)) {
        $inertia->errors($errors);
        return new RedirectResponse('/register');
    }

    // ...
}
```

On the next `GET /register`, `page.props.errors` will contain:

```json
{ "email": "This email is already taken.", "password": "Too short." }
```

### Named error bags

Group errors from multiple forms on the same page using named bags:

```php
$inertia->errors(['email' => 'Invalid.'], 'loginForm');
$inertia->errors(['name' => 'Required.'], 'profileForm');
```

Props will be:

```json
{
    "errors": {
        "loginForm":   { "email": "Invalid." },
        "profileForm": { "name": "Required." }
    }
}
```

### X-Inertia-Error-Bag header

When the client sends `X-Inertia-Error-Bag: myBag`, the bundle wraps the `default` bag under the given key:

```json
{ "errors": { "myBag": { "email": "Invalid." } } }
```

This allows a page with multiple independent forms to scope errors without naming the bag server-side.

---

## Symfony Form integration

To bridge Symfony Form validation with Inertia errors, add an event listener on `kernel.response` (or handle in the controller):

```php
use Symfony\Component\Form\FormInterface;

private function handleForm(FormInterface $form, Inertia $inertia): bool
{
    if ($form->isSubmitted() && !$form->isValid()) {
        $errors = [];
        foreach ($form->getErrors(deep: true) as $error) {
            $field = $error->getOrigin()?->getName() ?? 'global';
            $errors[$field] = $error->getMessage();
        }
        $inertia->errors($errors);
        return false;
    }
    return true;
}
```

# Style Guide Rule: Explicit, Sequential Code

**Mandatory.** Read and follow this entire file before writing or editing code. A feature, fix, refactor, or any other code change is **not done** until every changed file complies with this guide. Passing tests and Pint is not a substitute.

---

## Rule

Follow **PSR-12** formatting conventions for PHP.

Apply the same general PSR principles to JavaScript wherever they make sense, including consistent indentation, readable line length, clear spacing, one statement per line, and predictable formatting.

This style guide adds project-specific conventions that may be stricter than PSR-12.

The project favors:

- **Explicit over clever**
- **Explicit data flow**
- **One method call per line in method chains**
- **One logical operation per statement**
- **Sequential code that can be read from top to bottom**
- **KISS** — simple steps, not extra layers (see Keep It Simple below and `.cursor/rules/kiss.mdc`)

The goal is not to minimize the number of lines. The goal is to make the code immediately understandable, easy to debug, and easy to modify.

Sequential style does **not** mean more helpers. Named intermediate values are required; `normalize*` / `sanitize*` / custom parsers for trim, lowercase, or reading a file are not.

---

## Explicit Over Clever

Prefer code whose intent is immediately obvious over code that is shorter, more abstract, or more clever.

A developer should be able to understand what the code does without:

- Mentally evaluating deeply nested expressions
- Tracking multiple levels of parentheses
- Reconstructing hidden framework behavior
- Inferring relationships that exist only through naming conventions
- Jumping between several lifecycle methods to understand a simple operation

When two implementations solve the same problem, prefer the implementation that communicates its intent most clearly, even when it requires additional lines.

### Good

```php
$data = $request->validated();

$post->update($data);
```

### Avoid

```php
$post->update($request->validated());
```

Both examples are valid PHP, but the first makes each operation explicit:

1. Validate the request
2. Store the validated data
3. Update the post

This creates a clear inspection, logging, and debugging point.

---

## Keep It Simple (KISS)

Explicit sequential code is not a reason to add classes, helpers, regex, or config.

Extract a private method when it is a **business step** the reader needs a name for (`createLeadAndOpportunity`, `findDuplicate`). Do **not** extract a method whose only job is trim, lowercase, empty-to-null, punctuation stripping, or markdown heading scans.

### Good

```php
$companyName = strtolower(trim($name));
$notes = $lead['why_good_fit'] ?? null;
$prompt = trim((string) File::get($path));
```

### Avoid

```php
$companyName = $this->normalizeCompanyName($name);
$notes = $this->normalizeQualificationNotes($lead['why_good_fit'] ?? null);
$prompt = $this->extractSystemPromptSection($contents);
```

Do not use regex when `trim`, `strtolower`, `str_starts_with`, or `str_replace` is enough.

Hardcode values that will not change at runtime (approved prompt path, headings). Change them in git.

---

## Explicit Data Flow

Data should move through the application in visible, named steps.

Use meaningful intermediate variables when a value represents an important concept such as:

- Validated input
- A transformed payload
- A query result
- A service result
- A response body
- A constructed command, event, or mail object

Intermediate variables should clarify the flow rather than merely increase the number of lines.

### PHP

#### Good

```php
public function update(UpdatePostRequest $request, Post $post)
{
    $data = $request->validated();

    $post->update($data);
}
```

#### Avoid

```php
public function update(UpdatePostRequest $request, Post $post)
{
    $post->update($request->validated());
}
```

### JavaScript

#### Good

```js
const payload = form.validate();

await api.update(payload);
```

#### Avoid

```js
await api.update(form.validate());
```

---

## Methods Inside Methods

Avoid passing method calls, constructors, or complex expressions directly into other methods when the inner result represents a meaningful value.

Extract the result into a named variable first.

### PHP

#### Good

```php
$data = $request->validated();

$mail = new WelcomeMail($data);

Mail::to($user)
    ->send($mail);
```

#### Avoid

```php
Mail::to($user)->send(new WelcomeMail($request->validated());
```

#### Good 
**IMPORTANT**: Also pay attention to identation, covered later on file)

```php
$report = $reportService->generate($filters);

return response()
            ->json($report);
```

#### Avoid

```php
return response()->json(
    $reportService->generate($filters)
);
```

### JavaScript

#### Good

```js
const payload = form.validate();

const request = new UpdateRequest(payload);

client.send(request);
```

#### Avoid

```js
client.send(
    new UpdateRequest(form.validate())
);
```

#### Good

```js
const result = await service.generate(filters);

return Response.json(result);
```

#### Avoid

```js
return Response.json(
    await service.generate(filters)
);
```

---

## Method Chaining

When a statement contains a method chain, keep the initial expression and its first method call on the first line.

Place each subsequent chained method call on its own line.

This convention applies to PHP and JavaScript.

The purpose is to make the chain easy to scan vertically and to avoid compressing several operations into one dense line.

### Alignment (primary structure + one indent)

Continued `->` / `.` lines are indented **one indentation level (4 spaces) ahead of the primary structure of the chain**.

The **primary structure** is where the chain starts on the first line:

- the class or facade name after `=` (`Testimonial`, `CaseStudy`, `Route`, …)
- the receiver expression for an instance/relation chain (`$service`, `users`, …)

```text
$testimonials = Testimonial::inRandomOrder()
|<-- primary -->|
                    ->limit(10)
|<-- primary + 4 -->|
```

The same anchor applies everywhere: Eloquent, relations, Pest `->with()`, `Route::…`, array, multiline parameters, and JavaScript chains.

#### Good

```php
$testimonials = Testimonial::inRandomOrder()
                    ->limit(10)
                    ->get();
```

```php
$testimonials = $service->testimonials()
                    ->inRandomOrder()
                    ->limit(5)
                    ->get();
```

```php
Route::middleware(['auth'])
    ->prefix('core')
    ->name('core.')
    ->group(function () {
        // ...
    });
```

```
$foo =  bar(
            $a,
            $b,
            $c
        )
```

```php
public function $sendEmail (
    User $user,
    array $products,
    Address $address
) {
    // ...
}
```

```php
$cars = [
            'Audi',
            'Hyundai',
            'Kia',
            'Ford',
        ];
```

```php
it('smoke tests public web routes', function (string $url, string $text) {
    visit($url)
        ->assertSee($text);
})
->with([
    ['/', 'You do great work'],
]);
```

#### Avoid — same column as the primary structure

```php
$testimonials = Testimonial::inRandomOrder()
                ->limit(10)
                ->get();
```

#### Avoid — more than one indent ahead of the primary structure

```php
$testimonials = Testimonial::inRandomOrder()
                            ->limit(10)
                            ->get();
```

#### Avoid — compressed on one line

```php
$users = User::where('active', true)->orderBy('name')->get();
```

```php
Route::middleware(['auth'])->prefix('core')->name('core.')->group(function () {
    // ...
});
```

### Edge case — short receivers

If placing the continuation only one indent ahead of the primary structure does **not** create at least one full standard indentation (4 spaces) of visual hierarchy past a weak/same-level alignment, add **one extra tab** so the hierarchy stays obvious.

#### Avoid — aligned indent is weaker than one standard tab

```php
$faq = Faq::where('id', 1)
        ->first();
```

#### Good — one extra tab restores a clear hierarchy

```php
$faq = Faq::where('id', 1)
            ->first();
```

### JavaScript

Same rule: one indent ahead of the primary structure of the chain.

#### Good

```js
axios.post(url, payload)
    .then(response => {
        console.log('Data saved:', response.data);
    })
    .catch(error => {
        console.error('Submission failed:', error);
    });
```

```js
const names = users
                  .filter(activeUsers)
                  .sort(byName)
                  .map(getName);
```

#### Avoid

```js
axios.post(url, payload).then(response => {
    console.log('Data saved:', response.data);
}).catch(error => {
    console.error('Submission failed:', error);
});
```

```js
const names = users.filter(activeUsers).sort(byName).map(getName);
```

---

## Property Access and Dot Notation

Even when immediately understandable it should follow chaining conventions.

Do not mechanically extract every property into a separate variable.

Extract intermediate properties when the access chain is long, reused, optional, difficult to inspect, or represents a meaningful domain value.

### PHP

#### Acceptable

```php
$city = $user
            ->address
            ->city;
```

#### Prefer when the intermediate value matters

```php
$address = $user->address;

$city = $address->city;
```

### JavaScript

#### Acceptable

```js
const city = user
                .address
                .city;
```

#### Prefer when the intermediate value matters

```js
const address = user.address;

const city = address.city;
```

---

## One Logical Operation Per Statement

Each statement should communicate one clear operation.

Avoid combining validation, transformation, construction, persistence, and response generation into a single expression.

### PHP

#### Good

```php
$data = $request->validated();

$data['updated_by'] = $request->user()->id;

$post->update($data);
```

#### Avoid

```php
$post->update([
    ...$request->validated(),
    'updated_by' => $request->user()->id,
]);
```

### JavaScript

#### Good

```js
const payload = form.validate();

payload.updatedBy = currentUser.id;

await api.update(payload);
```

#### Avoid

```js
await api.update({
    ...form.validate(),
    updatedBy: currentUser.id,
});
```

The shorter version may be valid, but the sequential version exposes each meaningful step.

---

## Exceptions

Simple, conventional expressions that are immediately understandable may remain inline.

### PHP

```php
return view('posts.index');
```

```php
return redirect()->route('posts.index');
```

```php
abort_if($post === null, 404);
```

### JavaScript

```js
return items.length;
```

```js
console.log(response.data);
```

The objective is not to maximize line count. The objective is to reduce cognitive load.

---

## Decision Criteria

Before nesting calls, compressing a sequence, or writing a long chain on one line, ask:

> Would separating this into explicit steps make it easier to read, debug, review, or modify?

When the answer is yes, use intermediate variables or one chained method per line.

When the answer is no, the simpler inline expression is acceptable.

---

## Summary

Code should be understandable from top to bottom.

Prefer:

- Explicit intent
- Named intermediate values
- Visible data flow
    - You can and must break big methods into smaller methods with explicit intended names
    - Do **not** extract methods whose only job is trim, lowercase, empty-to-null, or regex cleanup (see Keep It Simple)
    ```
    public function createPodcast(PodastRequest $request)
    {
        $data = $request->validated();
        $podacast = Podcast::create($data);
        $listeners = $podcast
                        ->listeners
                        ->toArray();

        $this->translate($podcast);
        $this->summarize($podcast);
        $this->sendEmail($podcast->listeners);

        return response()->json(['status' => 'ok], 201);
    }

    private function translate(Podcast $podcast)
    {
        //...
    }

    private function summarize(Podcast $podcast)
    {
        //...
    }

    private function sendEmail(array $listeners)
    {
        //...
    }
    ```
- One logical operation per statement
- One chained method per line
- PSR-12 conventions in PHP
- Equivalent PSR-inspired conventions in JavaScript
- Readability over cleverness
- Maintainability over brevity

Optimize for the next developer reading the code, not for the fewest possible lines.

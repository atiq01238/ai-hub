{{--
    This is a plain, unstyled form on purpose — you don't have a public-facing
    site layout yet (only the admin panel). Once you build your public pages,
    move this content into whatever layout/design your visitors actually see.
    For now it's here so the feature works end-to-end.
--}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Review {{ $tool->name }}</title>
</head>
<body style="font-family: sans-serif; max-width: 480px; margin: 40px auto;">

    <h1>Rate {{ $tool->name }}</h1>

    @if (session('status'))
        <p style="color: green;">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <ul style="color: red;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('reviews.store', $tool->id) }}" method="POST">
        @csrf

        <label for="rating">Your rating (1–5)</label><br>
        <input type="number" id="rating" name="rating" min="1" max="5" step="0.5" required value="{{ old('rating') }}"><br><br>

        <label for="body">Your review (optional)</label><br>
        <textarea id="body" name="body" rows="4" style="width:100%;">{{ old('body') }}</textarea><br><br>

        <button type="submit">Submit review</button>
    </form>

</body>
</html>

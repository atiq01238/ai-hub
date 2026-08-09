{{--
    Plain unstyled form, same situation as the review form — you don't have
    a public site layout yet. Move this content into your real design later.
--}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Suggest a Tool</title>
</head>
<body style="font-family: sans-serif; max-width: 480px; margin: 40px auto;">

    <h1>Suggest an AI Tool</h1>

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

    <form action="{{ route('submissions.store') }}" method="POST">
        @csrf

        <label for="tool_name">Tool name</label><br>
        <input type="text" id="tool_name" name="tool_name" required value="{{ old('tool_name') }}" style="width:100%;"><br><br>

        <label for="submitted_by_email">Your email</label><br>
        <input type="email" id="submitted_by_email" name="submitted_by_email" required value="{{ old('submitted_by_email') }}" style="width:100%;"><br><br>

        <label for="website">Tool website</label><br>
        <input type="url" id="website" name="website" placeholder="https://" value="{{ old('website') }}" style="width:100%;"><br><br>

        <label for="category">Category</label><br>
        <input type="text" id="category" name="category" placeholder="e.g. Image Generation" value="{{ old('category') }}" style="width:100%;"><br><br>

        <label for="description">Why should we list it?</label><br>
        <textarea id="description" name="description" rows="4" style="width:100%;">{{ old('description') }}</textarea><br><br>

        <button type="submit">Submit suggestion</button>
    </form>

</body>
</html>

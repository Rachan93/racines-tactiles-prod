<!DOCTYPE html>
<html>
<head>
    <title>Form Test</title>
</head>
<body>
    <form method="POST" action="{{ route('test.bladePost') }}">
        @csrf
        <input type="text" name="nomPost" placeholder="Nom">
        <input type="text" name="prenomPost" placeholder="Prénom">
        <button type="submit">Envoyer</button>
    </form>

    @if ($errors->any())
        <div style="color:red;">
            <ul>
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</body>
</html>

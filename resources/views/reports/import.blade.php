<!DOCTYPE html>
<html>
<head>
    <title>Importer des rapports</title>
</head>
<body>
    <h1>Importer un fichier Excel</h1>

    @if(session('success'))
        <p style="color:green">{{ session('success') }}</p>
    @endif

    <form action="/import-reports" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" required>
        <button type="submit">Importer</button>
    </form>
</body>
</html>

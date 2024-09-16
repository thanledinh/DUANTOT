<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>
    <h1>Admin Dashboard</h1>
    <nav>
        <ul>
            <li><a href="{{ route('admin.products.index') }}">Products</a></li>
            <li><a href="{{ route('admin.categories.index') }}">Categories</a></li>
        </ul>
    </nav>
</body>
</html>
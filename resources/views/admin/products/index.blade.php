<!DOCTYPE html>
<html>
<head>
    <title>Products</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1>Products</h1>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Brand</th>
                    <th>Barcode</th>
                    <th>Variants</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                    <tr>
                        <td>{{ $product->id }}</td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->description }}</td>
                        <td>{{ $product->type }}</td>
                        <td>{{ $product->brand }}</td>
                        <td>{{ $product->barcode }}</td>
                        <td>
                            <button class="btn btn-info" data-toggle="collapse" data-target="#variants-{{ $product->id }}">
                                View Variants
                            </button>
                            <div id="variants-{{ $product->id }}" class="collapse">
                                <ul class="list-group mt-2">
                                    @foreach($product->variants as $variant)
                                        <li class="list-group-item">
                                            <strong>Variant ID:</strong> {{ $variant->id }}<br>
                                            <strong>Price:</strong> {{ number_format($variant->price, 2) }}<br>
                                            <strong>Stock Quantity:</strong> {{ $variant->stock_quantity }}<br>
                                            <strong>Size:</strong> {{ $variant->size }}<br>
                                            <strong>Flavor:</strong> {{ $variant->flavor }}<br>
                                            @if($variant->image)
                                                <strong>Image:</strong> <img src="{{ asset($variant->image) }}" alt="Variant Image" style="width: 50px; height: auto;">
                                            @else
                                                <strong>Image:</strong> N/A
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
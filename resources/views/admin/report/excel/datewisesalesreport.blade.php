<table>
  <thead>
    <tr>
      <th>P-Code </th>
      <th>Product </th>
      <th>Date </th>
      <th>Category</th>
      <th>Sub Category</th>
      <th>Sub Sub Category</th>
      <th>Brand </th>
      <th>Total Sale Qty</th>
    </tr>
  </thead>
  <tbody>
    @php
        $total_sold_qty = 0;
        $total_buying_amount = 0;
        $total_sales_amount = 0;
    @endphp
  @foreach($product as $value)
    <tr>
      <td>{{ $value->product->id }}</td>
      <td>{{ $value->product->product_name }}</td>
      <td>{{ $value->date }}</td>
      <td>{{ $value->category->category_name }}</td>
      <td>{{ $value->sub_category->sub_category_name }}</td>
      <td>{{ $value->sub_sub_category->sub_sub_category_name }}</td>
      <td>{{ $value->brand->brand_name }}</td>
      <td>{{ $value->total_sold_qty }}</td>
    </tr>
    @php
        $total_sold_qty += $value->total_sold_qty;
    @endphp
  @endforeach
    <tr>
      <td colspan="7">Total =</td>
      <td>{{ $total_sold_qty }}</td>
    </tr>
  </tbody>
</table>

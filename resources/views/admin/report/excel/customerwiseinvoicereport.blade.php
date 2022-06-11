<table>
  <thead>
    <tr>
        <th>Customer</th>
        <th>Order Date</th>
        <th>Order Id</th>
        <th>Order Qty</th>
        <th>Total Buying Amount</th>
        <th>Total Sales Amount</th>
        <th>Profit</th>
    </tr>
  </thead>
  <tbody>
  @foreach($product as $value)
    <tr>
        <td>{{ $value->user->name }}</td>
        <td>{{ $value->order_date }}</td>
        <td>{{ $value->order_id }}</td>
        <td>{{ $value->total_sold_qty }}</td>
        <td>{{ $value->total_buying_price }}</td>
        <td>{{ $value->total_selling_price }}</td>
        <td>{{ $value->total_selling_price - $value->total_buying_price }}</td>
    </tr>
  @endforeach
  </tbody>
</table>

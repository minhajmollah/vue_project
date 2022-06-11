<table>
  <thead>
    <tr>
        <th>Order Date</th>
        <th>Total Order</th>
        <th>Order Qty</th>
        <th>Total Buying Amount</th>
        <th>Total Sales Amount</th>
        <th>Profit</th>
    </tr>
  </thead>
  <tbody>
  @foreach($product as $value)
    <tr>
        <td>{{ $value->order_date }}</td>
        <td>{{ $value->total_order }}</td>
        <td>{{ $value->total_sold_qty }}</td>
        <td>{{ $value->total_buying_price }}</td>
        <td>{{ $value->total_selling_price }}</td>
        <td>{{ $value->total_selling_price - $value->total_buying_price }}</td>
    </tr>
  @endforeach
  </tbody>
</table>

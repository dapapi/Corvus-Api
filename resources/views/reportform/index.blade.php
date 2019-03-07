<!doctype html>
<html >
    <head>

    </head>
    <table>
        <thead>
                <tr><td colspan='1'>     </td><td colspan='1'>       </td>
                    <td colspan='1'>接触数量 </td><td colspan='1'>数量占比</td>
                    <td colspan='1'>接触环比增量</td><td colspan='1'>接触同比增量</td>
                    <td colspan='1'>达成数量</td>
                    <td colspan='1'>达成环比增量 </td><td colspan='1'>达成同比增量 </td><td colspan='1'>客户转化率 </td>
                </tr>
                <tr><td colspan='1'>     </td><td colspan='1'>       </td>
                    <td colspan='1'>{{$invoices['sum'] }}</td>
                    <td colspan='1'>{{$invoices['ratio_sum']*100..'%'}}</td>
                    <td colspan='1'>{{$invoices['ring_ratio_increment_sum']}}</td>
                    <td colspan='1'>{{$invoices['annual_ratio_increment_sum']}}</td>
                    <td colspan='1'>{{$invoices['confirm_annual_increment_sum']}}</td>
                    <td colspan='1'>{{$invoices['confirm_ratio_increment_sum']}} </td>
                    <td colspan='1'>{{$invoices['confirm_sum']}} </td>
                    <td colspan='1'>{{$invoices['customer_conversion_rate_sum']*100..'%' }} </td>
                </tr>

        </thead>
    <tbody>
    @foreach($invoices['data']['industry_data'] as $key => $val)

       <tr>
           <td colspan='1'>{{$key = $key==0?'品类':''}}</td>
           <td colspan='1'>{{$val->name}}</td>
           <td colspan='1'>{{$val->number}}</td>
           <td colspan='1'>{{$val->ratio*100..'%'}}</td>
           <td colspan='1'>{{$val->ring_ratio_increment}}</td>
           <td colspan='1'>{{$val->annual_increment}}</td>
           <td colspan='1'>{{$val->confirm_number}}</td>
           <td colspan='1'>{{$val->confirm_annual_increment}}</td>
           <td colspan='1'>{{$val->confirm_ratio_increment }}</td>
           <td colspan='1'>{{$val->customer_conversion_rate.'%'}}</td>
       </tr>

    @endforeach
    @foreach($invoices['data']['cooperation_data'] as $key => $val)
        <tr>
                <td colspan='1'>{{$key = $key==0?'合作':''}}</td>
                <td colspan='1'>{{$val->name}}</td>
                <td colspan='1'>{{$val->number}}</td>
                <td colspan='1'>{{$val->ratio*100..'%'}}</td>
                <td colspan='1'>{{$val->ring_ratio_increment}}</td>
                <td colspan='1'>{{$val->annual_increment}}</td>
                <td colspan='1'>{{$val->confirm_number}}</td>
                <td colspan='1'>{{$val->confirm_annual_increment}}</td>
                <td colspan='1'>{{$val->confirm_ratio_increment }}</td>
                <td colspan='1'>{{$val->customer_conversion_rate.'%'}}</td>
        </tr>
    @endforeach
    @foreach($invoices['data']['resource_type_data'] as $key => $val)
        <tr>
                <td colspan='1'>{{$key = $key==0?'线索来源':''}}</td>
                <td colspan='1'>{{$val->name}}</td>
                <td colspan='1'>{{$val->number}}</td>
                <td colspan='1'>{{$val->ratio*100..'%'}}</td>
                <td colspan='1'>{{$val->ring_ratio_increment}}</td>
                <td colspan='1'>{{$val->annual_increment}}</td>
                <td colspan='1'>{{$val->confirm_number}}</td>
                <td colspan='1'>{{$val->confirm_annual_increment}}</td>
                <td colspan='1'>{{$val->confirm_ratio_increment }}</td>
                <td colspan='1'>{{$val->customer_conversion_rate.'%'}}</td>
        </tr>
    @endforeach
    @foreach($invoices['data']['priority_data']  as $key => $val)
        <tr>
                <td colspan='1'>{{$key = $key==0?'优先级':''}}</td>
                <td colspan='1'>{{$val->name}}</td>
                <td colspan='1'>{{$val->number}}</td>
                <td colspan='1'>{{$val->ratio*100..'%'}}</td>
                <td colspan='1'>{{$val->ring_ratio_increment}}</td>
                <td colspan='1'>{{$val->annual_increment}}</td>
                <td colspan='1'>{{$val->confirm_number}}</td>
                <td colspan='1'>{{$val->confirm_annual_increment}}</td>
                <td colspan='1'>{{$val->confirm_ratio_increment }}</td>
                <td colspan='1'>{{$val->customer_conversion_rate.'%'}}</td>
        </tr>
    @endforeach

    </tbody>
    </table>
</html>

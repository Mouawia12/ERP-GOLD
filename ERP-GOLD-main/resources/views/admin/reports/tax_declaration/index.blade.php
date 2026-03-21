@extends('admin.layouts.master')
@section('content')
    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif
<!-- row opened -->
<style> 
    tr th{
         padding: 10px;
         padding-right:20px;
    }
     
    tr {
        border: 1px solid #fff;
    }
    tr td {
     padding: 5px;
     padding-right:20px;
    }


@media print{
    @page {
       
        margin: 0 !important;
        padding:0 !important;
    }

 
}
</style>

<div class="row row-sm">
    <div class="col-xl-12">  
            <div class="card-body px-0 pt-0 pb-2"> 
                <div class="card shadow mb-3 ">
                    <div class="card-header py-3 " id="head-right"  style="direction: rtl;border:solid 1px gray"> 
                        <div class="row">
                            <div class="col-6 text-right"> 
                                 <b>الرقم الضريبي: {{''}}</b>
                            
                            </div>
                            <div class="col-6 text-left"> 
                                 <b>Tax Number : {{''}}</b>
                            
                            </div>
                            <div class="clearfix"></div>
                            
                        </div> 
                        <hr>
                        <div class="row">
                            <div class="col-3 text-right"> 
                               <br> التاريخ :{{$periodFrom}}  
                               <br><button type="button" class="btn btn-primary btnPrint" id="btnPrint"><i class="fa fa-print"></i></button>
                            </div>   
                            <div class="col-6 text-center"> 
                                <br>
                                <h4> <b>الاقرار الضريبي </b>
                                       <br><br>[ {{$periodFrom}} - {{$periodTo}} ]</h4>
 
                            </div>
                            <div class="col-3 text-left"> 
                                <br> 
                                <img alt="user-vat"  width="70" height="70"
                                    src="{{URL::asset('assets/img/vat.png')}}">
                                
                            </div>
                          </div>
                        </div>
                    </div> 
                </div> 
            </div>  
                
                <div class="table-responsive hoverable-table" id="d-table"  style="direction: rtl;"> 
                    <table class="display w-100  text-nowrap " id="" 
                       style="text-align: center;direction: rtl;">
                        <thead>
                            <tr>
                                <th class="text-right">
                                    ضريبة القيمة المضافة بالريال
                                </th>
                                <th class="text-right">التعديلات بالريال</th>
                                <th class="text-right">المبلغ بالريال</th> 
                                <th class="btn-primary bg-primary text-white"> ضريبة على المبيعات</th> 
    
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td  class="btn-secondary bg-secondary text-white text-right">{{ number_format($salesTaxTotal,2) }}</td>
                                <td class="text-right">0</td>
                                <td class="text-right">  {{  number_format($salesTotal,2) }}</td>
                                <td class="text-right">1. المبيعات الخاضعة للنسبة الاساسية 15% </td>
                            </tr>
                            <tr>
                                <td  class="btn-secondary bg-secondary text-white text-right">00.</td>
                                <td class="text-right">0</td>
                                <td class="text-right">00.</td> 
                                <td class="text-right">2. المبيعات الخاضعة للنسبة الاساسية 5% </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td class="text-right">0</td>
                                <td class="text-right">00.</td>
                                <td class="text-right">3. المبيعات للمواطنين (الخدمات الصحية الخاصة/التعليم الاهلي الخاص /المسكن الاول)</td>
                            </tr> 
                            <tr>
                                <td class="text-right"></td>
                                <td class="text-right">0</td>
                                <td class="text-right">{{ number_format($salesZeroTotal,2) }}</td>
                                <td class="text-right">4. المبيعات المحلية الخاضعة للنسبة الصفرية</td>
                            </tr>  
                            <tr>
                                <td></td>
                                <td class="text-right">0</td>
                                <td class="text-right">00.</td>
                                <td class="text-right">5. الصادرات</td>
                            </tr> 
                            <tr>
                                <td></td>
                                <td class="text-right">0</td>
                                <td class="text-right">00.</td>
                                <td class="text-right">6. المبيعات المعفاه</td>
                            </tr> 
                            <tr>
                                <th class="bg-primary text-white text-right"> 
                                    {{ number_format($salesFinalTaxTotal,2) }}
                                </th>
                                <th class="bg-primary text-white text-right">0</th>
                                <th class="bg-primary text-white text-right">
                                   {{ number_format($salesFinalTotal,2) }}
                                </th>
                                <th class="bg-primary text-white text-right">7 . اجمالي المبيعات</th>
                            </tr> 
                        </tbody>
                        <tr><td colspan="4"></td></tr>  
                        <thead>
                            <tr>
                                <th colspan="3"></th> 
                                <th class="bg-info text-white"> ضريبة على المشتريات</th> 
    
                            </tr>
                        </thead> 
                        <tbody>
                            <tr>
                                <td  class="bg-secondary text-white text-right">{{number_format($purchaseTaxTotal,2)}}</td>
                                <td class="text-right">0</td>
                                <td class="text-right">{{number_format($purchaseTotal,2)}}</td>
                                <td class="text-right">8. المشتريات الخاضعة للنسبة الاساسية 15% </td>
                            </tr> 
                            <tr>
                                <td  class="bg-secondary text-white text-right">00.</td>
                                <td class="text-right">0</td>
                                <td class="text-right">00.</td>
                                <td class="text-right">9. المشتريات الخاضعة للنسبة الاساسية 5% </td>
                            </tr> 
                            <tr>
                                <td  class="bg-secondary text-white text-right">00.</td>
                                <td class="text-right">0</td>
                                <td class="text-right">00.</td>
                                <td class="text-right">10. الاستيرادات الخاضعة لضريبة القيمة المضافة التي تدفع في الجمارك 15%</td>
                            </tr> 
                            <tr>
                                <td  class="bg-secondary text-white text-right">00.</td>
                                <td class="text-right">0</td>
                                <td class="text-right">00.</td>
                                <td class="text-right">11. الاستيرادات الخاضعة لضريبة القيمة المضافة التي تدفع في الجمارك 5% </td>
                            </tr> 
                            <tr>
                                <td  class="bg-secondary text-white text-right">00.</td>
                                <td class="text-right">0</td>
                                <td class="text-right">00.</td>
                                <td class="text-right">12. الاستيرادات الخاضعة لضريبة القيمة المضافة التي تطبق عليها الية الاحتساب العكسي</td>
                            </tr> 
                            <tr>
                                <td  class="text-right"></td>
                                <td class="text-right">0</td>
                                <td class="text-right">{{number_format($purchaseZeroTotal,2)}}</td>
                                <td class="text-right">13. المشتريات الخاضعة للنسبة الصفرية</td>
                            </tr>
                            <tr>
                                <td  class="text-right"></td>
                                <td class="text-right">0</td>
                                <td class="text-right">00.</td>
                                <td class="text-right">14. المشتريات المعفاه</td>
                            </tr>  
                            <tr>
                                <th class="bg-info text-white text-center">{{ number_format($purchaseFinalTaxTotal,2)}}</th>
                                <th class="bg-info text-white text-right">0</th>
                                <th class="bg-info text-white text-right">
                                    {{ number_format($purchaseFinalTotal,2)}}
                                </th>
                                <th class="bg-info text-white text-right">15. اجمالي المشتريات</td>
                            </tr> 
                        </tbody> 
                        <tbody>
                            <tr>
                                <td colspan="3" class="bg-secondary text-white text-center">{{ number_format($fullTaxTotal,2) }}</td> 
                                <td class="text-right">16. اجمالي ضريبة القيمة المضافة المستحقة عن الفترة الحالية</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="bg-secondary text-white text-center">0</td> 
                                <td class="text-right">17. تصحيحات من الفترات السابقة بين  +- 5000 ريال</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="bg-secondary text-white text-center">0</td> 
                                <td class="text-right">18.  ضريبة القيمة المضافة التي تم ترحيلها من الفترة / الفترات السابقة</td>
                            </tr> 
                        <thead>
                            <tr>
                                <th colspan="3" class="bg-success text-white text-center">
                                    {{ number_format($fullTaxTotal,2) }}</th> 
                                <th class="bg-success text-white text-right">19 .  صافي الضريبة المستحقة او المستردة</th>
                            </tr> 
                        </thead>    
                        </tbody>    
                    </table>
                </div>      
        </div>
        <!-- End of Main Content --> 
    </div>
    <!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->
  
@endsection 
<script src="{{asset('assets/js/jquery.min.js')}}"></script> 
 
<script type="text/javascript">
    let id = 0;


    $(document).ready(function () {
        $(document).on('click', '#btnPrint', function (event) {
            printPage();
        });

    });

    function printPage(){
        var css = '',
            head = document.head || document.getElementsByTagName('head')[0],
            style = document.createElement('style');

        style.type = 'text/css';
        style.media = 'print';

        if (style.styleSheet){
            style.styleSheet.cssText = css;
        } else {
            style.appendChild(document.createTextNode(css));
        }

        head.appendChild(style);
        document.getElementById("main-header").style.display = 'none';
        document.getElementById("main-footer").style.display = 'none'; 
        document.getElementById("back-to-top").style.display = 'none';
        document.getElementById("btnPrint").style.display = 'none';  
        window.print();
        document.getElementById("main-header").style.display = 'block';
        document.getElementById("main-footer").style.display = 'block'; 
        document.getElementById("back-to-top").style.display = 'block'; 
        document.getElementById("btnPrint").style.display = 'block'; 
    }
</script> 
 




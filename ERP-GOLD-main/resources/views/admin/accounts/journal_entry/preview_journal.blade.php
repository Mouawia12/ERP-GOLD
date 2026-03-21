
<div class="modal fade" id="paymentsModal" tabindex="-1" role="dialog" aria-labelledby="smallModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document"  style="min-width: 700px">
        <div class="modal-content">
            <div class="modal-header">
            <button type="button" class="close modal-close-btn close-create" data-bs-dismiss="modal"
                        aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="smallBody">
            <h3 class="alert alert-primary text-center">دفتر اليومية</h3>
                <table  id="sTable" class="table items table-striped table-bordered table-condensed table-hover text-center">
                    <thead>
                        <tr>
                            <th>{{__('رقم اليومية')}}</th>
                            <th>{{__('main.code')}}</th>
                            <th>{{__('main.name')}}</th>
                            <th>{{__('main.Debit')}}</th>
                            <th>{{__('main.Credit')}}</th>
                            <th class="col-md-3">{{__('main.notes')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                       <?php
$debit = 0;
$credit = 0;
?> 
                        @foreach($journal->documents as $document)
                            <tr>
                               <td>{{$document->id}}</td>
                                <td>{{$document->account->code}}</td>
                                <td>{{$document->account->name}}</td>
                                <td>{{round($document->debit, 2)}}</td>
                                <td>{{round($document->credit, 2)}}</td>
                                <td>{{$document->notes}}</td>
                                <?php
                                $debit += $document->debit;
                                $credit += $document->credit;
                                ?>

                            </tr>
                        @endforeach 
                           <tr class="bg-primary text-white"> 
                                <td colspan="3">الاجمالي</td>
                                <td>{{round($debit, 2)}}</td>
                                <td>{{round($credit, 2)}}</td>
                                <td></td>
                            </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

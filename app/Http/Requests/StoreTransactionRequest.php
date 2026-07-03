<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Account;
use App\Models\Transaction;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_posted' => ['required', 'boolean'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'journalEntries' => ['required', 'array', 'min:2', function (string $attribute, mixed $value, \Closure $fail) {
                $totalDebit = 0;
                $totalCredit = 0;
                foreach($value as $entry) {
                    if($entry['type'] == 'debit') $totalDebit += $entry['amount'];
                    if($entry['type'] == 'credit') $totalCredit += $entry['amount'];
                }

                if(abs($totalCredit - $totalDebit) > 0.0001) {
                    $fail("Сумма дебета ($totalDebit) должна равняться сумме кредита ($totalCredit)");
                }
            }],
            'journalEntries.*.type' => ['required', 'in:debit,credit'],
            'journalEntries.*.amount' => ['required', 'numeric', 'min:0.01'],
            'journalEntries.*.account_id' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $account = Account::find($value);
                    if (!$account || !$account->is_active) {
                        $fail("Выбранный счёт ($account[name]) неактивен.");
                    }
                },
            ],
        ];
    }
}

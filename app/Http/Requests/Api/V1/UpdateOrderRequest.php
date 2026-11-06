<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('order'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required_without:address_id', Rule::enum(OrderStatus::class)],
            'address_id' => [
                'required_without:status',
                'integer',
                Rule::exists('addresses', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $order = $this->route('order');
                $nextStatus = OrderStatus::tryFrom((string) $this->input('status'));

                if ($nextStatus && ! $order->status->canTransitionTo($nextStatus)) {
                    $validator->errors()->add(
                        'status',
                        "Não é possível mudar o status de {$order->status->value} para {$nextStatus->value}."
                    );
                }

                if ($this->has('address_id') && $order->status !== OrderStatus::Pending) {
                    $validator->errors()->add('address_id', 'O endereço só pode ser alterado em pedidos pendentes.');
                }
            },
        ];
    }
}

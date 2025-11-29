<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'ip_address', 'is_processed', 'discount_coupon'];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getCart($authCheck = true)
    {
        if ($authCheck && auth('sanctum')->check()) {
            return self::firstOrCreate([
                'user_id' => auth('sanctum')->id(),
                'is_processed' => 0
            ]);
        }

        return self::firstOrCreate([
            'ip_address' => request()->ip(),
            'is_processed' => 0
        ]);
    }

    public function addProduct($data)
    {
        $product = Product::find($data['product_id']);
        $price = $product->discounted_price;

        $cartItem = CartItem::where([
            'product_id' => $data['product_id'],
            'cart_id' => $this->id
        ]);

        if (isset($data['product_attributes'])) {
            $variant = ProductVariant::where('product_id', $product->id)
                ->where('attributes', $data['product_attributes'])
                ->first();
            if ($variant) {
                $price = $variant->discounted_price;
            }
        }

        $cartItem = $cartItem->first();

        if ($cartItem) {
            $cartItem->update([
                'quantity' => $cartItem->quantity + $data['quantity']
            ]);
        } else {
            CartItem::create([
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'],
                'cart_id' => $this->id,
                'vendor_id' => $product->vendor_id,
                'price' => $price,
                'product_attributes' => $data['product_attributes'] ?? []
            ]);
        }
    }

    public function getCartItemTotal()
    {
        return $this->items->sum(fn($item) => $item->price * $item->quantity);
    }

    public function markAsDone()
    {
        $this->update(['is_processed' => 1]);
        $this->items()->delete();
    }
}

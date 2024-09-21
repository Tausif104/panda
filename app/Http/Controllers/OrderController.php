<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserExam;
use App\Models\OrderPackage;
use App\Models\Payment;
use App\Models\Exam;
use App\Models\Package;
use Stripe\Stripe;
use Carbon\Carbon;
use Stripe\PaymentIntent;
use Auth;

class OrderController extends Controller
{
    public function createOrder(Request $request)
    {
        try {
            $request->validate([
                'exam_id' => 'required|exists:exams,id',
                'package_id' => 'required|exists:packages,id',
            ]);
    
            $user = Auth::user();
            $examId = $request->exam_id;
            $packageId = $request->package_id;
    
            // Create UserExam entry
            $userExam = UserExam::create([
                'user_id' => $user->id,
                'exam_id' => $examId,
            ]);
    
            // Create OrderPackage entry with pending subscription dates
            $orderPackage = OrderPackage::create([
                'package_id' => $packageId,
                'user_exam_id' => $userExam->id,
                'subscription_start_date' => null,
                'subscription_end_date' => null,
            ]);
    
            // Initialize Stripe payment
            $package = Package::findOrFail($packageId);
            $amount = $package->price * 100; // Stripe amount is in cents
    
            Stripe::setApiKey(env('STRIPE_SECRET'));
    
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'usd',
                'payment_method_types' => ['card'],
            ]);

            $this->response->data = [
                'clientSecret' => $paymentIntent->client_secret,
                'orderPackageId' => $orderPackage->id,
            ];
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    public function handlePayment(Request $request)
    {
        try {
            $request->validate([
                'orderPackageId' => 'required|exists:order_packages,id',
                'paymentMethodId' => 'required|string',
            ]);

            $orderPackage = OrderPackage::findOrFail($request->orderPackageId);
            $user = Auth::user();

            // Confirm the Stripe payment
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $paymentIntent = PaymentIntent::retrieve($request->paymentMethodId);
            $paymentIntent->confirm();

            if ($paymentIntent->status == 'succeeded') {
                // Update OrderPackage subscription dates
                $startDate = now();
                $endDate = now()->addMonth();

                $orderPackage->update([
                    'subscription_start_date' => $startDate,
                    'subscription_end_date' => $endDate,
                ]);

                // Insert data into payments table
                Payment::create([
                    'order_package_id' => $orderPackage->id,
                    'payment_method' => 'stripe',
                    'transaction_id' => $paymentIntent->id,
                    'amount' => $paymentIntent->amount / 100, // amount in dollars
                    'reference' => $paymentIntent->charges->data[0]->receipt_url,
                    'currency' => $paymentIntent->currency,
                    'description' => $paymentIntent->description,
                    'user_id' => $user->id,
                    'status' => 'completed',
                ]);

                $this->response->message[] = 'Payment successful';
                return $this->response(200);
            } else {
                $this->response->error[] = 'Payment failed';
                return $this->response(400);
            }
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }

    public function getUserExams()
    {
        try {
            $user = Auth::user();

            $validExams = UserExam::where('user_id', $user->id)
            ->whereHas('orderPackages', function ($query) {
                $query->whereDate('subscription_start_date', '<=', Carbon::now())
                      ->whereDate('subscription_end_date', '>=', Carbon::now());
            })
            ->with('exam')
            ->get();

            $this->response->data['userExams'] = $validExams;
            return $this->response(200);
        } catch (\Exception $e) {
            $this->response->error[] = $e->getMessage();
            return $this->response(500);
        }
    }
}

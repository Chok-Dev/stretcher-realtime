<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use RealRashid\SweetAlert\Facades\Alert;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'password' => 'required',
            'action' => 'required|in:user,admin'
        ], [
            'name.required' => '* กรุณาใส่ชื่อผู้ใช้',
            'password.required' => '* กรุณาใส่รหัสผ่าน',
        ]);

        if ($request->action === 'user') {
            return $this->loginUser($request);
        } else {
            return $this->loginAdmin($request);
        }
    }

    private function loginUser(Request $request)
    {
      
        $user = DB::connection('pgsql')->table('stretcher_team_list as s')
            ->leftJoin('doctor as d', 'd.code', 's.stretcher_team_list_doctor')
            ->leftJoin('opduser as o', 'o.doctorcode', 'd.code')
            ->where('loginname', $request->name)
            ->where('passweb', strtoupper(md5($request->password)))
            ->select('s.*', 'o.loginname', 'o.passweb', 'o.name', 'o.doctorcode')
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'password' => ['* เฉพาะศูนย์เปล'],
            ]);
        }

        Session::put([
            'doctorcode' => $user->doctorcode,
            'name' => $user->name,
            'userid' => $user->stretcher_team_list_id,
            'user_type' => 'stretcher_team'
        ]);

        Alert::success('สำเร็จ', 'เข้าสู่ระบบสำเร็จ');
        return redirect()->route('dashboard');
    }

    private function loginAdmin(Request $request)
    {
        $user = DB::connection('pgsql')->table('opduser as o')
            ->leftJoin('doctor as d', 'd.code', 'o.doctorcode')
            ->where('loginname', $request->name)
            ->where('passweb', strtoupper(md5($request->password)))
            ->select('o.loginname', 'o.passweb', 'o.name', 'o.doctorcode')
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'password' => ['* รหัสผ่านไม่ถูกต้อง'],
            ]);
        }

        Session::put([
            'doctorcode' => $user->doctorcode,
            'name' => $user->name,
            'user_type' => 'admin'
        ]);

        Alert::success('สำเร็จ', 'เข้าสู่ระบบสำเร็จ');
        return redirect()->route('public.view');
    }

    public function logout()
    {
        Session::flush();
        Alert::success('สำเร็จ', 'ออกจากระบบแล้ว');
        return redirect()->route('login');
    }
}
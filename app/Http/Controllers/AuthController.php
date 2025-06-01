<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        if ($request->action == "user") {
            return $this->loginUser($request);
        } elseif ($request->action == "admin") {
            return $this->loginAdmin($request);
        }

        return back()->withErrors(['action' => 'Invalid action']);
    }

    private function loginUser(Request $request)
    {
       $request->validate([
    'name' => [
        'required',
        Rule::exists('pgsql.opduser', 'loginname'),
    ],
    'password' => 'required',
], [
    'name.required' => '* กรุณาใส่ชื่อผู้ใช้',
    'name.exists' => '* ไม่พบชื่อผู้ใช้นี้',
    'password.required' => '* กรุณาใส่รหัสผ่าน',
]);

        $user = DB::connection('pgsql')->table('stretcher_team_list as s')
            ->leftJoin('doctor as d', 'd.code', 's.stretcher_team_list_doctor')
            ->leftJoin('opduser as o', 'o.doctorcode', 'd.code')
            ->where('loginname', $request->name)
            ->where('passweb', strtoupper(md5($request->password)))
            ->select('s.*', 'o.loginname', 'o.passweb', 'o.name', 'o.doctorcode')
            ->first();

        if (!empty($user)) {
            Session::put('doctorcode', $user->doctorcode);
            Session::put('name', $user->name);
            Session::put('userid', $user->stretcher_team_list_id);
            Session::put('user_type', 'team_member');

            return redirect()->route('dashboard')->with('success', 'เข้าสู่ระบบสำเร็จ');
        } else {
            throw ValidationException::withMessages([
                'password' => ['* เฉพาะศูนย์เปล'],
            ]);
        }
    }

    private function loginAdmin(Request $request)
    {
       $request->validate([
    'name' => [
        'required',
        Rule::exists('pgsql.opduser', 'loginname'),
    ],
    'password' => 'required',
], [
    'name.required' => '* กรุณาใส่ชื่อผู้ใช้',
    'name.exists' => '* ไม่พบชื่อผู้ใช้นี้',
    'password.required' => '* กรุณาใส่รหัสผ่าน',
]);

        $user = DB::connection('pgsql')->table('opduser as o')
            ->leftJoin('doctor as d', 'd.code', 'o.doctorcode')
            ->where('loginname', $request->name)
            ->where('passweb', strtoupper(md5($request->password)))
            ->select('o.loginname', 'o.passweb', 'o.name', 'o.doctorcode')
            ->first();

        if (!empty($user)) {
            Session::put('doctorcode', $user->doctorcode);
            Session::put('name', $user->name);
            Session::put('user_type', 'admin');

            return redirect()->route('dashboard')->with('success', 'เข้าสู่ระบบสำเร็จ');
        } else {
            throw ValidationException::withMessages([
                'password' => ['* รหัสผ่านไม่ถูกต้อง'],
            ]);
        }
    }

    public function logout()
    {
        Session::flush();
        return redirect()->route('login.form')->with('success', 'ออกจากระบบเรียบร้อยแล้ว');
    }
}


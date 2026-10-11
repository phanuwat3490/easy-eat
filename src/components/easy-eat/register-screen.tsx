import { useState, type FormEvent } from 'react';
import { Link } from '@tanstack/react-router';
import { ArrowLeft, Check, ShieldCheck, Smartphone, UserRound } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Field, ImageUpload, Notice, PageHeading, PasswordField } from './form-parts';
import { registrationOtpSchema, registrationSchema, type RegistrationDetails } from './registration-schema';
import { easyEatApi } from '@/lib/easy-eat-api';

const initialDetails: RegistrationDetails = { firstname: '', lastname: '', email: '', phone: '', password: '', confirmPassword: '' };

export function RegisterScreen() {
  const [details, setDetails] = useState(initialDetails);
  const [otpSent, setOtpSent] = useState(false);
  const [otp, setOtp] = useState('');
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);

  // ส่วนยืนยัน OTP จะแสดงเมื่อกรอกเบอร์โทรศัพท์ครบ 10 หลักแล้วเท่านั้น
  const phoneReady = /^\d{10}$/.test(details.phone);

  function change(key: keyof RegistrationDetails, value: string) {
    setDetails(previous => ({ ...previous, [key]: value }));
    if (key === 'phone') { setOtpSent(false); setOtp(''); }
    setMessage('');
  }

  async function sendOtp() {
    if (!phoneReady) {
      setMessage('กรุณากรอกเบอร์โทรศัพท์ 10 หลักก่อนขอรหัส OTP');
      return;
    }

    try {
      // ตรวจสอบเบอร์โทรศัพท์ซ้ำในระบบก่อนส่ง OTP (ส่ง member_id เป็นค่าว่างสำหรับการสมัครใหม่)
      const checkRes: any = await easyEatApi.checkPhone(details.phone, '');
      if (checkRes && checkRes.exists) {
        setMessage('เบอร์โทรศัพท์นี้ถูกใช้งานในระบบแล้ว');
        setOtpSent(false);
        return;
      }

      // จำลองการขอ OTP ผ่านระบบ
      setOtpSent(true);
      setOtp('');
      setMessage('ส่งรหัส OTP เรียบร้อยแล้ว (สามารถกรอกเลข 6 หลักใดๆ เพื่อทดสอบได้)');
    } catch (error) {
      setMessage('เกิดข้อผิดพลาดในการตรวจสอบเบอร์โทรศัพท์หรือส่งรหัส OTP');
    }
  }

  async function submit(event: FormEvent) {
    event.preventDefault();
    const result = registrationSchema.safeParse(details);
    if (!result.success) {
      setMessage(result.error.issues[0]?.message ?? 'กรุณาตรวจสอบข้อมูล');
      return;
    }
    
    try {
      // ตรวจสอบเบอร์ซ้ำอีกครั้งก่อนกดบันทึกสมัครสมาชิก
      const checkRes: any = await easyEatApi.checkPhone(details.phone, '');
      if (checkRes && checkRes.exists) {
        setMessage('เบอร์โทรศัพท์นี้ถูกใช้งานในระบบแล้ว');
        return;
      }
    } catch (error) {
      setMessage('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
      return;
    }

    if (!otpSent) {
      setMessage('กรุณากดขอรหัส OTP เพื่อยืนยันเบอร์โทรศัพท์ก่อนสมัครสมาชิก');
      return;
    }
    const otpResult = registrationOtpSchema.safeParse(otp);
    if (!otpResult.success) {
      setMessage(otpResult.error.issues[0]?.message ?? 'กรุณาตรวจสอบรหัส OTP');
      return;
    }

    setLoading(true);
    try {
      const response = await easyEatApi.register({
        firstname: details.firstname,
        lastname: details.lastname,
        phone: details.phone,
        email: details.email,
        password: details.password
      });

      if (response && response.success) {
        setMessage('สมัครสมาชิกสำเร็จ! กำลังพากลับไปหน้าเข้าสู่ระบบ...');
        setTimeout(() => {
          window.location.href = '/login';
        }, 1500);
      } else {
        setMessage(response?.message || 'อีเมลนี้ถูกใช้งานแล้ว หรือเกิดข้อผิดพลาดในการสมัคร');
        setLoading(false);
      }
    } catch (error) {
      setMessage('ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ฐานข้อมูลได้');
      setLoading(false);
    }
  }

  return (
    <>
      <PageHeading title="สมัครสมาชิก" eyebrow="JOIN EASY EAT" description="เริ่มต้นมื้อดี ๆ ไปด้วยกัน" icon={UserRound}/>
      <div className="grid grid-cols-1 gap-8 border-t pt-7 lg:grid-cols-[minmax(220px,.75fr)_minmax(0,1.25fr)] lg:gap-12">
        <aside className="min-w-0">
          <h2 className="font-display text-3xl font-semibold leading-snug">Easy Eat<span className="text-primary">.</span></h2>
          <p className="mt-3 text-lg font-medium">มื้อโปรดครั้งถัดไป<br/>เริ่มต้นที่นี่</p>
          <div className="mt-6 flex items-center gap-3 border-t pt-5 text-primary"><ShieldCheck size={22}/><span className="text-sm">Easy Eat Member</span></div>
        </aside>
        <div className="min-w-0 lg:border-l lg:pl-10">
          <form onSubmit={submit} noValidate className="space-y-5">
            <ImageUpload portrait/>
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
              <Field label="ชื่อจริง" id="register-firstname" name="firstname" autoComplete="given-name" required maxLength={100} value={details.firstname} onChange={e => change('firstname', e.target.value)}/>
              <Field label="นามสกุล" id="register-lastname" name="lastname" autoComplete="family-name" required maxLength={100} value={details.lastname} onChange={e => change('lastname', e.target.value)}/>
            </div>
            <Field label="เบอร์โทรศัพท์ (10 หลัก)" id="register-phone" name="phone" type="tel" inputMode="numeric" autoComplete="tel" placeholder="0812345678" required maxLength={10} value={details.phone} onChange={e => change('phone', e.target.value.replace(/\D/g, ''))}/>
            {phoneReady && (
              <div className="space-y-4 rounded-md border bg-secondary/40 p-4">
                <div className="flex items-center gap-2 text-sm font-medium text-primary"><ShieldCheck size={18}/>ยืนยันเบอร์โทรศัพท์ {details.phone}</div>
                {otpSent ? (
                  <>
                    <Field label="รหัส OTP 6 หลัก" id="register-otp" name="otp" inputMode="numeric" autoComplete="one-time-code" placeholder="000000" required maxLength={6} value={otp} onChange={e => { setOtp(e.target.value.replace(/\D/g, '')); setMessage(''); }}/>
                    <p className="text-xs text-muted-foreground">รหัสมีอายุ 3 นาทีหลังส่งสำเร็จ</p>
                    <Button type="button" variant="ghost" className="h-auto w-full whitespace-normal" onClick={sendOtp}>ขอรหัส OTP ใหม่</Button>
                  </>
                ) : (
                  <Button type="button" variant="outline" className="w-full" onClick={sendOtp}><Smartphone/>ขอรหัส OTP ผ่าน SMS</Button>
                )}
              </div>
            )}
            <Field label="อีเมล" id="register-email" name="email" type="email" autoComplete="email" placeholder="you@example.com" required maxLength={255} value={details.email} onChange={e => change('email', e.target.value)}/>
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
              <PasswordField label="รหัสผ่าน" id="register-password" name="password" autoComplete="new-password" required maxLength={128} aria-describedby="register-password-rule" value={details.password} onChange={e => change('password', e.target.value)}/>
              <PasswordField label="ยืนยันรหัสผ่าน" id="register-confirm" name="confirmPassword" autoComplete="new-password" required maxLength={128} value={details.confirmPassword} onChange={e => change('confirmPassword', e.target.value)}/>
            </div>
            <p id="register-password-rule" className="flex items-start gap-2 text-xs leading-relaxed text-muted-foreground"><Check size={15} className="shrink-0 text-primary"/>อย่างน้อย 8 ตัวอักษร มีตัวอักษรภาษาอังกฤษและตัวเลข</p>
            {message && <Notice>{message}</Notice>}
            <Button type="submit" size="lg" className="w-full" disabled={loading}><UserRound/>{loading ? 'กำลังบันทึกข้อมูล...' : 'สมัครสมาชิก'}</Button>
          </form>
          <p className="mt-6 text-center text-sm text-muted-foreground">มีบัญชีอยู่แล้ว? <Link to="/login" className="font-medium text-primary">เข้าสู่ระบบ</Link></p>
          <div className="mt-6 border-t pt-4"><Button asChild variant="ghost" className="w-full"><Link to="/"><ArrowLeft/>กลับหน้าหลัก</Link></Button></div>
        </div>
      </div>
    </>
  );
}
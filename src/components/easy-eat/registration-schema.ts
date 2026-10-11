import { z } from 'zod';

export const registrationSchema = z.object({
  firstname: z.string().trim().min(1, 'กรุณากรอกชื่อจริง').max(100, 'ชื่อจริงยาวเกินไป'),
  lastname: z.string().trim().min(1, 'กรุณากรอกนามสกุล').max(100, 'นามสกุลยาวเกินไป'),
  email: z.string().trim().email('กรุณากรอกอีเมลให้ถูกต้อง').max(255),
  phone: z.string().regex(/^\d{10}$/, 'กรุณากรอกเบอร์โทรศัพท์ 10 หลักให้ถูกต้อง'),
  password: z.string().max(128).regex(/^(?=.*[A-Za-z])(?=.*\d).{8,}$/, 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร และมีตัวอักษรภาษาอังกฤษกับตัวเลข'),
  confirmPassword: z.string().max(128),
}).refine(data => data.password === data.confirmPassword, {
  path: ['confirmPassword'], message: 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน',
});
export const registrationOtpSchema = z.string().regex(/^\d{6}$/, 'กรุณากรอกรหัส OTP 6 หลัก');
export const registrationOtpValiditySeconds = 3 * 60;
export type RegistrationDetails = z.infer<typeof registrationSchema>;
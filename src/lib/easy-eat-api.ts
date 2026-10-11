export const apiBaseUrl = (import.meta.env as Record<string, any>)["VITE_API_BASE_URL"] ?? "http://localhost/api";

async function request<T>(path: string, init: RequestInit = {}): Promise<T | null> {
  try {
    const response = await fetch(`${apiBaseUrl}${path}`, {
      credentials: "include",
      ...init,
      headers: { "Content-Type": "application/json", ...init.headers },
    });
    return (await response.json()) as T;
  } catch {
    return null;
  }
}

export interface LoginResult {
  success: boolean;
  message?: string;
  user?: { id: string; role: "admin" | "member"; firstname: string; lastname: string; email: string; phone: string; profile_pic?: string };
}
export interface QueueItem { id: string; queue_number: string; customer_name: string; date: string; time: string; people: number; status: string }
export interface MenuItem { id: string; name: string; price: number; category?: string; is_active: boolean; image?: string }
export interface Member { id: string; firstname: string; lastname: string; email: string; phone: string; is_active: boolean; image?: string; profile_pic?: string }
export interface ReportData { success: boolean; statusReport: { status: string; count: number }[]; dailyReport: { date: string; count: number }[] }

export const easyEatApi = {
  login: (email: string, password: string) => request<LoginResult>("/login.php", { method: "POST", body: JSON.stringify({ email, password }) }),
  logout: () => request<{ success: boolean }>("/logout.php", { method: "POST" }),
  register: (payload: { firstname: string; lastname: string; phone: string; email: string; password: string }) => request<{ success: boolean; message: string }>("/register.php", { method: "POST", body: JSON.stringify(payload) }),
  sendOtp: (phone: string) => request<{ sent: boolean }>("/send-otp.php", { method: "POST", body: JSON.stringify({ phone }) }),
  checkPhone: (phone: string, memberId: string | number) => request<{ success: boolean; exists: boolean; message?: string }>("/check_phone.php", { method: "POST", body: JSON.stringify({ phone, member_id: memberId }) }),
  booking: (payload: { member_id: string; date: string; time: string; people: number }) => request<{ success: boolean; id?: string; queue_number?: string; message?: string }>("/booking.php", { method: "POST", body: JSON.stringify(payload) }),
  addWalkInQueue: (payload: { firstname: string; lastname: string; people: number; service_datetime: string }) => request<{ success: boolean; id?: string; queue_number?: string; message?: string }>("/add_walkin_queue.php", { method: "POST", body: JSON.stringify(payload) }),
  customerHome: (memberId: number | string) => request<{ currentQueue: QueueItem | null; history: QueueItem[]; menuItems: MenuItem[] }>(`/customer-home.php?member_id=${memberId}`),
  
  cancelQueue: (id: string) => request<{ success: boolean; cancelled?: boolean; message?: string }>("/cancel-queue.php", { method: "POST", body: JSON.stringify({ id }) }),
  
  updateQueueStatus: (id: string, status: string) => request<{ success: boolean; message?: string }>("/update_queue_status.php", { method: "POST", body: JSON.stringify({ id, status }) }),
  updateQueueTime: (id: string, status: string, new_time: string) => request<{ success: boolean; message?: string }>("/update_queue_status.php", { method: "POST", body: JSON.stringify({ id, status, new_time }) }),
  reports: () => request<ReportData>("/reports.php"),
  getQueues: () => request<QueueItem[]>("/get_queues.php"),
  getMyQueues: (memberId: number | string) => request<QueueItem[]>(`/get_queues.php?member_id=${memberId}`),
  getNotifications: (memberId: number | string) => request<{ id: string; message: string; created_at: string; is_read: number; queue_id: string }[]>(`/get_notifications.php?member_id=${memberId}`),
  checkStatus: (memberId: number | string) => request<{ success: boolean; is_active: boolean }>(`/check_status.php?member_id=${memberId}`),
  menu: () => request<MenuItem[]>("/menu.php"),
  saveMenu: (payload: { id?: string; name: string; category: string; price: number; image?: string; is_active: boolean }) => request<{ success: boolean; message: string }>("/save_menu.php", { method: "POST", body: JSON.stringify(payload) }),
  deleteMenu: (id: string) => request<{ success: boolean; message: string }>("/delete_menu.php", { method: "POST", body: JSON.stringify({ id }) }),
  members: () => request<Member[]>("/members.php"),
  updateMember: (payload: { id: string; firstname: string; lastname: string; email: string; phone: string; password?: string; profile_pic?: string; is_active: boolean }) => request<{ success: boolean; message: string }>("/update_member.php", { method: "POST", body: JSON.stringify(payload) }),
  updateAdmin: (payload: { id: string; firstname: string; lastname: string; email: string; phone: string; password?: string; profile_pic?: string }) => request<{ success: boolean; message: string }>("/update_admin.php", { method: "POST", body: JSON.stringify(payload) }),
};
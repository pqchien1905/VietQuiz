/**
 * VietQuiz i18n — Vietnamese Translation Module
 * Ported from src/contexts/locale-context.tsx
 */

const _translations = {
  // Navigation
  "nav.dashboard": "Bảng điều khiển",
  "nav.courses": "Khóa học",
  "nav.quizzes": "Bài kiểm tra",
  "nav.questionBank": "Ngân hàng câu hỏi",
  "nav.assignments": "Bài tập",
  "nav.grades": "Điểm số",
  "nav.classes": "Lớp học",
  "nav.myClasses": "Lớp của Tôi",
  "nav.results": "Kết quả",
  "nav.analytics": "Phân tích",
  "nav.grading": "Chấm điểm",
  "nav.students": "Học sinh",
  "nav.settings": "Cài đặt",
  "nav.profile": "Hồ sơ",
  "nav.help": "Trợ giúp & Hỗ trợ",
  "nav.notifications": "Thông báo",
  "nav.logout": "Đăng xuất",
  "nav.joinClass": "Tham gia lớp",
  "nav.vip": "Nâng VIP",
  "nav.trash": "Thùng rác",

  // Common
  "common.search": "Tìm kiếm",
  "common.save": "Lưu",
  "common.cancel": "Hủy",
  "common.delete": "Xóa",
  "common.edit": "Chỉnh sửa",
  "common.view": "Xem",
  "common.create": "Tạo mới",
  "common.update": "Cập nhật",
  "common.submit": "Gửi",
  "common.loading": "Đang tải...",
  "common.noData": "Không có dữ liệu",
  "common.student": "Học sinh",
  "common.students": "Học sinh",
  "common.teacher": "Giáo viên",
  "common.studentPortal": "Cổng Học sinh",
  "common.teacherPortal": "Cổng Giáo viên",
  "common.export": "Xuất",
  "common.exportResults": "Xuất Kết quả",
  "common.active": "Hoạt động",
  "common.inactive": "Không hoạt động",
  "common.pending": "Đang chờ",
  "common.completed": "Hoàn thành",
  "common.graded": "Đã chấm",
  "common.submitted": "Đã nộp",
  "common.draft": "Nháp",
  "common.published": "Đã xuất bản",
  "common.overdue": "Quá hạn",
  "common.inProgress": "Đang thực hiện",
  "common.notStarted": "Chưa bắt đầu",
  "common.total": "Tổng số",
  "common.all": "Tất cả",
  "common.filter": "Lọc",
  "common.name": "Tên",
  "common.email": "Email",
  "common.score": "Điểm",
  "common.grade": "Điểm số",
  "common.class": "Lớp",
  "common.quiz": "Bài kiểm tra",
  "common.quizzes": "Bài kiểm tra",
  "common.assignment": "Bài tập",
  "common.assignments": "Bài tập",
  "common.course": "Khóa học",
  "common.courses": "Khóa học",
  "common.question": "Câu hỏi",
  "common.questions": "Câu hỏi",
  "common.answer": "Câu trả lời",
  "common.progress": "Tiến độ",
  "common.startQuiz": "Bắt đầu",
  "common.submitQuiz": "Nộp bài",
  "common.viewDetails": "Xem Chi tiết",
  "common.viewResults": "Xem Kết quả",
  "common.saveChanges": "Lưu thay đổi",
  "common.back": "Quay lại",
  "common.next": "Tiếp theo",
  "common.continue": "Tiếp tục",
  "common.finish": "Hoàn thành",
  "common.myAccount": "Tài khoản của tôi",
  "common.profile": "Hồ sơ",
  "common.settings": "Cài đặt",
  "common.helpSupport": "Trợ giúp & Hỗ trợ",
  "common.logOut": "Đăng xuất",
  "common.avgScore": "Điểm Trung bình",
  "common.dueDate": "Hạn nộp",
  "common.actions": "Hành động",
  "common.status": "Trạng thái",
  "common.description": "Mô tả",
  "common.overview": "Tổng quan",
  "common.viewAll": "Xem tất cả",
  "theme.light": "Sáng",
  "theme.dark": "Tối",
  "theme.system": "Hệ thống",

  // Auth
  "auth.welcomeBack": "Chào mừng trở lại",
  "auth.signInToAccount": "Đăng nhập vào tài khoản của bạn để tiếp tục",
  "auth.signIn": "Đăng nhập",
  "auth.chooseRoleCredentials": "Chọn vai trò và nhập thông tin đăng nhập",
  "auth.iAmA": "Tôi là...",
  "auth.teacher": "Giáo viên",
  "auth.student": "Học sinh",
  "auth.emailAddress": "Địa chỉ Email",
  "auth.password": "Mật khẩu",
  "auth.emailRequired": "Email là bắt buộc",
  "auth.validEmail": "Vui lòng nhập địa chỉ email hợp lệ",
  "auth.passwordRequired": "Mật khẩu là bắt buộc",
  "auth.passwordMin6": "Mật khẩu phải có ít nhất 6 ký tự",
  "auth.invalidCredentials": "Email hoặc mật khẩu không hợp lệ. Vui lòng thử lại.",
  "auth.forgotPassword": "Quên mật khẩu?",
  "auth.rememberMe": "Ghi nhớ tôi trong 30 ngày",
  "auth.signingIn": "Đang đăng nhập...",
  "auth.signInButton": "Đăng nhập",
  "auth.noAccount": "Chưa có tài khoản?",
  "auth.createAccount": "Tạo tài khoản",
  "auth.demoCredentials": "Thông tin đăng nhập Demo:",
  "auth.teacherDemo": "Giáo viên: teacher@demo.com / password123",
  "auth.studentDemo": "Học sinh: student@demo.com / password123",
  "auth.emailPlaceholder": "ten.ban@example.com",
  "auth.namePlaceholder": "Nguyễn Văn A",

  // Register
  "register.createYourAccount": "Tạo tài khoản của bạn",
  "register.joinThousands": "Tham gia hàng nghìn nhà giáo dục và học sinh",
  "register.signUp": "Đăng ký",
  "register.fullName": "Họ và Tên",
  "register.fullNameRequired": "Họ và tên là bắt buộc",
  "register.phoneNumber": "Số Điện thoại",
  "register.institution": "Cơ quan",
  "register.confirmPassword": "Xác nhận Mật khẩu",
  "register.passwordsNotMatch": "Mật khẩu không khớp",
  "register.passwordMin8": "Mật khẩu phải có ít nhất 8 ký tự",
  "register.iAgreeTo": "Tôi đồng ý với",
  "register.termsOfService": "Điều khoản Dịch vụ",
  "register.privacyPolicy": "Chính sách Bảo mật",
  "register.creatingAccount": "Đang tạo tài khoản...",
  "register.createAccount": "Tạo tài khoản",
  "register.alreadyHaveAccount": "Đã có tài khoản?",
  "register.signIn": "Đăng nhập",

  // Dashboard
  "dashboard.welcome": "Chào mừng trở lại",
  "dashboard.overview": "Tổng quan",
  "dashboard.overviewDescription": "Chào mừng trở lại! Đây là tổng quan về các hoạt động giảng dạy của bạn.",
  "dashboard.recentActivity": "Hoạt động gần đây",
  "dashboard.upcomingQuizzes": "Bài kiểm tra sắp tới",
  "dashboard.createExam": "Tạo Bài thi",
  "dashboard.totalClasses": "Tổng số Lớp",
  "dashboard.totalExams": "Tổng số Bài thi",
  "dashboard.totalSubmissions": "Tổng số Bài nộp",
  "dashboard.avgScore": "Điểm TB",
  "dashboard.thisWeek": "tuần này",
  "dashboard.vsLastMonth": "so với tháng trước",
  "dashboard.recentExams": "Bài thi Gần đây",
  "dashboard.viewAll": "Xem Tất cả",
  "dashboard.noExamsYet": "Chưa có bài thi nào",
  "dashboard.createFirstExam": "Tạo Bài thi Đầu tiên",
  "dashboard.quickAccess": "Truy cập Nhanh",

  // Classes
  "classes.myClasses": "Lớp của Tôi",
  "classes.manageDescription": "Quản lý lớp học, xem danh sách đăng ký và theo dõi tiến độ học sinh.",
  "classes.createClass": "Tạo Lớp",
  "classes.createNewClass": "Tạo Lớp Mới",
  "classes.className": "Tên Lớp",
  "classes.subject": "Môn học",
  "classes.classCode": "Mã Lớp",
  "classes.totalClasses": "Tổng số Lớp",
  "classes.totalStudents": "Tổng số Học sinh",
  "classes.avgPerformance": "Hiệu suất TB",
  "classes.schedule": "Lịch",
  "classes.viewRoster": "Xem Danh sách Học sinh",
  "classes.noClasses": "Chưa có Lớp nào",
  "classes.createFirstClass": "Tạo Lớp Đầu tiên",
  "classes.classSettings": "Cài đặt Lớp",
  "classes.archiveClass": "Lưu trữ Lớp",

  // Quizzes (Teacher)
  "quizzes.title": "Bài kiểm tra & Kỳ thi",
  "quizzes.description": "Tạo, quản lý và phân tích bài kiểm tra và kỳ thi của bạn",
  "quizzes.createNew": "Tạo Kỳ thi Mới",
  "quizzes.searchPlaceholder": "Tìm kiếm kỳ thi theo tiêu đề hoặc lớp...",
  "quizzes.totalExams": "Tổng số Kỳ thi",
  "quizzes.active": "Đang hoạt động",
  "quizzes.drafts": "Nháp",
  "quizzes.closed": "Đã đóng",
  "quizzes.all": "Tất cả",
  "quizzes.questions": "câu hỏi",
  "quizzes.min": "phút",
  "quizzes.submissions": "bài nộp",
  "quizzes.viewResults": "Xem Kết quả",
  "quizzes.edit": "Chỉnh sửa",
  "quizzes.duplicate": "Nhân bản",
  "quizzes.delete": "Xóa",
  "quizzes.noExams": "Chưa có kỳ thi nào",
  "quizzes.createFirst": "Tạo Kỳ thi Đầu tiên",
  "quizzes.noFound": "Không tìm thấy kỳ thi",

  // Assignments
  "assignments.title": "Bài tập",
  "assignments.description": "Tạo và quản lý bài tập của học sinh",
  "assignments.createNew": "Tạo Bài tập",
  "assignments.active": "Đang hoạt động",
  "assignments.grading": "Đang chấm",
  "assignments.completed": "Hoàn thành",
  "assignments.submissions": "Bài nộp",
  "assignments.viewSubmissions": "Xem Bài nộp",
  "assignments.startGrading": "Bắt đầu Chấm điểm",
  "assignments.noAssignments": "Không tìm thấy bài tập nào",

  // Grading
  "grading.title": "Chấm điểm",
  "grading.description": "Xem xét và chấm bài nộp của học sinh",
  "grading.feedback": "Phản hồi",
  "grading.feedbackPlaceholder": "Cung cấp phản hồi mang tính xây dựng cho học sinh...",
  "grading.saveNext": "Lưu & Tiếp theo",
  "grading.gradeSubmission": "Chấm Bài nộp",
  "grading.graded": "Đã chấm",

  // Analytics
  "analytics.title": "Phân tích",
  "analytics.description": "Thông tin chi tiết về hiệu suất và sự tham gia của học sinh",
  "analytics.totalStudents": "Tổng số Học sinh",
  "analytics.activeClasses": "Lớp Đang hoạt động",
  "analytics.avgPerformance": "Hiệu suất TB",
  "analytics.completionRate": "Tỷ lệ Hoàn thành",
  "analytics.classPerformance": "Tổng quan Hiệu suất Lớp",
  "analytics.topPerformers": "Học sinh Xuất sắc Nhất",
  "analytics.weeklyActivity": "Xu hướng Hoạt động Hàng tuần",
  "analytics.studentEngagement": "Sự Tham gia của Học sinh",

  // Students
  "students.title": "Học sinh",
  "students.description": "Quản lý và theo dõi hiệu suất học sinh",
  "students.totalStudents": "Tổng số Học sinh",
  "students.averageScore": "Điểm Trung bình",
  "students.searchPlaceholder": "Tìm kiếm học sinh theo tên hoặc email...",
  "students.filterByClass": "Lọc theo Lớp",
  "students.noStudents": "Không tìm thấy học sinh",

  // Notifications
  "notifications.title": "Thông báo",
  "notifications.all": "Tất cả",
  "notifications.unread": "Chưa đọc",
  "notifications.read": "Đã đọc",
  "notifications.markAllRead": "Đánh dấu tất cả đã đọc",
  "notifications.clearAll": "Xóa tất cả",
  "notifications.noNotifications": "Không có thông báo",
  "notifications.allCaughtUp": "Bạn đã xem hết! Không có thông báo chưa đọc.",

  // Settings
  "settings.title": "Cài đặt",
  "settings.description": "Quản lý tùy chọn tài khoản và cài đặt ứng dụng",
  "settings.general": "Chung",
  "settings.notifications": "Thông báo",
  "settings.security": "Bảo mật",
  "settings.account": "Tài khoản",
  "settings.saveChanges": "Lưu thay đổi",
  "settings.profile": "Hồ sơ",
  "settings.fullName": "Họ và Tên",
  "settings.emailAddress": "Địa chỉ Email",
  "settings.phoneNumber": "Số Điện thoại",
  "settings.institution": "Cơ quan",
  "settings.changePassword": "Đổi Mật khẩu",
  "settings.currentPassword": "Mật khẩu Hiện tại",
  "settings.newPassword": "Mật khẩu Mới",
  "settings.confirmPassword": "Xác nhận Mật khẩu Mới",
  "settings.updatePassword": "Cập nhật Mật khẩu",
  "settings.dangerZone": "Khu vực Nguy hiểm",
  "settings.deleteAccount": "Xóa Tài khoản",
  "settings.language": "Ngôn ngữ",
  "settings.theme": "Chủ đề",
  "settings.appearance": "Giao diện",

  // Profile
  "profile.myProfile": "Hồ Sơ Của Tôi",
  "profile.editProfile": "Chỉnh sửa Hồ sơ",
  "profile.totalStudents": "Tổng số Học sinh",
  "profile.coursesTaught": "Khóa học Đã dạy",
  "profile.quizzesCreated": "Bài kiểm tra Đã tạo",

  // Help
  "help.header.title": "Trợ giúp và Hỗ trợ",
  "help.header.description": "Tìm câu trả lời cho các câu hỏi thường gặp hoặc liên hệ với đội ngũ hỗ trợ",
  "help.faqs": "Câu hỏi thường gặp",
  "help.contactSupport": "Liên hệ Hỗ trợ",
  "help.submitTicket": "Gửi Yêu cầu",
  "help.liveChat": "Chat Trực tiếp",
  "help.startChat": "Bắt đầu Chat",
  "help.submitSupportTicket": "Gửi Yêu cầu Hỗ trợ",

  // VIP
  "vip.title": "Nâng cấp tài khoản",
  "vip.upgrade": "Nâng cấp ngay",
  "vip.noCard": "Không cần thẻ tín dụng • Huỷ bất cứ lúc nào",
  "vip.startTrial": "Dùng thử Pro miễn phí 7 ngày",
  "vip.plan.free": "Miễn phí",
  "vip.plan.plus": "Giáo viên Plus",
  "vip.plan.pro": "Giáo viên Pro",

  // Join Class
  "joinClass.title": "Tham gia lớp học",
  "joinClass.subtitle": "Nhập mã lớp do giáo viên cung cấp",
  "joinClass.classCode": "Mã lớp học",
  "joinClass.findClass": "Tìm lớp học",
  "joinClass.joinClass": "Tham gia lớp",

  // Question Bank
  "qbank.title": "Ngân hàng câu hỏi",
  "qbank.subtitle": "Kho câu hỏi tập trung — tìm kiếm, phân loại và thêm vào đề thi chỉ trong vài giây",
  "qbank.addQuestion": "Thêm câu hỏi",
  "qbank.importFile": "Import từ file",
  "qbank.searchPlaceholder": "Tìm kiếm theo nội dung câu hỏi...",
  "qbank.allQuestions": "Tất cả",
  "qbank.mcq": "Trắc nghiệm",
  "qbank.trueFalse": "Đúng/Sai",
  "qbank.essay": "Tự luận",
  "qbank.fillBlank": "Điền khuyết",
  "qbank.difficulty.easy": "Dễ",
  "qbank.difficulty.medium": "Trung bình",
  "qbank.difficulty.hard": "Khó",
  "qbank.editQuestion": "Chỉnh sửa",
  "qbank.deleteQuestion": "Xóa",

  // Student
  "student.welcome": "Chào mừng trở lại, {name}! 👋",
  "student.dashboard.welcome": "Chào mừng trở lại! Đây là tổng quan của bạn.",
  "student.dashboard.enrolledCourses": "Khóa học Đã đăng ký",
  "student.dashboard.pendingQuizzes": "Bài kiểm tra Cần làm",
  "student.dashboard.assignmentsDue": "Bài tập Đến hạn",
  "student.dashboard.upcomingTasks": "Nhiệm vụ Sắp tới",
  "student.dashboard.viewAllTasks": "Xem Tất cả Nhiệm vụ",

  // Grades
  "grades.subtitle": "Theo dõi kết quả học tập trên tất cả các khóa học",
  "grades.overallAverage": "Điểm Trung bình Tổng thể",
  "grades.gradedItems": "Đã chấm điểm",
  "grades.allGrades": "Tất cả Điểm số",
  "grades.noGrades": "Không tìm thấy điểm số",
  "grades.noGradesAvailable": "Chưa có điểm số nào",

  // Results
  "results.title": "Kết quả Bài kiểm tra",
  "results.avgScore": "Điểm Trung bình",
  "results.passingRate": "Tỷ lệ Đỗ",
  "results.submissions": "Bài nộp",
  "results.viewDetails": "Xem Chi tiết",
  "results.export": "Xuất Kết quả",

  // Quiz taking
  "quiz.timeRemaining": "Thời gian còn lại",
  "quiz.questionsAnswered": "Số câu đã trả lời",
  "quiz.totalQuestions": "Tổng số Câu hỏi",
  "quiz.submitQuiz": "Nộp bài",
  "quiz.results": "Kết quả bài kiểm tra",
  "quiz.passed": "Chúc mừng! Bạn đã đạt",
  "quiz.failed": "Rất tiếc, bạn chưa đạt",
  "quiz.backToList": "Danh sách bài kiểm tra",
  "quiz.retake": "Làm lại",
  "quiz.reviewAnswers": "Xem lại đáp án",

  // Courses
  "course.activeCourses": "Khóa học Đang học",
  "course.continueLearning": "Tiếp tục học",
  "course.reviewCourse": "Xem lại khóa học",
  "course.noCourses": "Không tìm thấy khóa học",

  // Trash
  "trash.title": "Thùng rác",
  "trash.description": "Các mục đã xóa sẽ được lưu giữ trong 30 ngày trước khi xóa vĩnh viễn",
  "trash.empty": "Thùng rác trống",
  "trash.restore": "Khôi phục",
  "trash.deletePermanently": "Xóa Vĩnh viễn",
  "trash.emptyTrash": "Dọn Thùng rác",

  // Time
  "time.today": "Hôm nay",
  "time.yesterday": "Hôm qua",
  "time.tomorrow": "Ngày mai",

  // Landing
  "home.hero.title": "Nâng cao chất lượng",
  "home.hero.titleHighlight": " Kiểm tra & Đánh giá",
  "home.hero.title2": " cùng công nghệ AI",
  "home.hero.subtitle": "Tạo đề kiểm tra nhanh chóng, quản lý ngân hàng câu hỏi thông minh, chấm điểm tự động và theo dõi tiến độ học sinh với nền tảng giáo dục toàn diện hàng đầu Việt Nam.",
  "home.hero.cta.teacher": "Dùng thử miễn phí",
  "home.hero.cta.student": "Đăng nhập học sinh",
  "home.nav.login": "Đăng nhập",
  "home.nav.register": "Đăng ký miễn phí",
  "home.cta.subtitle": "Tham gia cùng 400,000+ giáo viên đang sử dụng VietQuiz mỗi ngày. Miễn phí, không cần thẻ tín dụng.",
  "home.cta.button": "Đăng ký miễn phí",
  "home.footer.copyright": "© 2026 VietQuiz. Nền tảng Kiểm tra Đánh giá Toàn diện — Made in Vietnam.",

  // Student profile
  "student.profile.description": "Quản lý thông tin cá nhân và xem tiến độ học tập của bạn",
};

/**
 * Translate a key with optional interpolation
 * @param {string} key
 * @param {Object} params - e.g. { name: 'Chiến' }
 * @returns {string}
 */
export function t(key, params = {}) {
  let text = _translations[key] ?? key;
  if (params && typeof params === 'object') {
    for (const [k, v] of Object.entries(params)) {
      text = text.replace(`{${k}}`, String(v));
    }
  }
  return text;
}

/**
 * Apply translations to all elements with [data-i18n] attribute
 * Usage: <span data-i18n="nav.dashboard"></span>
 *        <input placeholder="" data-i18n-placeholder="search.quizzes">
 */
export function applyTranslations(root = document) {
  root.querySelectorAll('[data-i18n]').forEach(el => {
    el.textContent = t(el.dataset.i18n);
  });
  root.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
    el.placeholder = t(el.dataset.i18nPlaceholder);
  });
  root.querySelectorAll('[data-i18n-title]').forEach(el => {
    el.title = t(el.dataset.i18nTitle);
  });
  root.querySelectorAll('[data-i18n-aria-label]').forEach(el => {
    el.setAttribute('aria-label', t(el.dataset.i18nAriaLabel));
  });
}

export default { t, applyTranslations };

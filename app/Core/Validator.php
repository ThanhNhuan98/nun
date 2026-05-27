<?php

namespace App\Core;

use App\Exceptions\ValidationException;

class Validator
{
    private array $data;
    private array $errors = [];
    private array $rules;
    private static array $messages = [
        'required' => ':field không được để trống.',
        'email' => ':field phải là một địa chỉ email hợp lệ.',
        'phone' => ':field không phải là số điện thoại hợp lệ.',
        'min' => ':field phải có ít nhất :rule ký tự.',
        'max' => ':field không được vượt quá :rule ký tự.',
        'in' => ':field được chọn không hợp lệ.',
        'unique' => ':field đã tồn tại trong hệ thống.',
        'numeric' => ':field phải là một số.',
        'float' => ':field phải là một số thực.',
        'gt' => ':field phải lớn hơn :rule.',
        'gte' => ':field phải lớn hơn hoặc bằng :rule.',
        'password_match' => 'Mật khẩu xác nhận không khớp.',
        'datetime' => ':field không phải là ngày giờ hợp lệ.',
        'after_now' => ':field phải là thời gian trong tương lai.',
        'before_one_week' => ':field không được hẹn quá 1 tuần kể từ hiện tại.',
    ];

    private static array $fieldNames = [
        'sender_name' => 'Tên người gửi',
        'sender_phone' => 'Số điện thoại người gửi',
        'receiver_name' => 'Tên người nhận',
        'receiver_phone' => 'Số điện thoại người nhận',
        'pickup_address' => 'Địa chỉ lấy hàng',
        'pickup_address_detail' => 'Địa chỉ chi tiết điểm lấy',
        'delivery_address' => 'Địa chỉ giao hàng',
        'delivery_address_detail' => 'Địa chỉ chi tiết điểm giao',
        'weight' => 'Khối lượng',
        'scheduled_at' => 'Thời gian hẹn lấy hàng',
        'note' => 'Ghi chú',
        'payment_method' => 'Phương thức thanh toán',
        'shipping_method' => 'Gói dịch vụ',
        'license_plate' => 'Biển số xe',
        'account' => 'Tài khoản',
        'password' => 'Mật khẩu',
        'password_confirm' => 'Xác nhận mật khẩu',
        'otp' => 'Mã xác thực OTP'
    ];

    // Khởi tạo bộ xác thực dữ liệu (Validator) với mảng dữ liệu đầu vào.
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    // Phân tích và chạy kiểm tra dữ liệu dựa trên mảng quy tắc (rules) được cung cấp.
    public function validate(array $rules): self
    {
        $this->rules = $rules;
        foreach ($rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            $rulesArray = explode('|', $ruleString);

            foreach ($rulesArray as $rule) {
                $ruleName = $rule;
                $ruleParam = null;

                if (strpos($rule, ':') !== false) {
                    [$ruleName, $ruleParam] = explode(':', $rule, 2);
                }

                $methodName = 'rule' . ucfirst($ruleName);
                if (method_exists($this, $methodName)) {
                    if (!$this->$methodName($field, $value, $ruleParam)) {
                        $this->addError($field, $ruleName, $ruleParam);
                        break; // Dừng kiểm tra các rule khác của field này nếu đã có lỗi
                    }
                }
            }
        }
        return $this;
    }

    // Thêm thông báo lỗi (đã được định dạng) vào mảng lỗi cho một trường cụ thể.
    private function addError(string $field, string $rule, ?string $param = null): void
    {
        $message = self::$messages[$rule] ?? 'Lỗi xác thực không xác định.';
        $this->errors[$field][] = str_replace([':field', ':rule'], [$this->getFieldName($field), $param], $message);
    }

    // Lấy tên hiển thị thân thiện (tiếng Việt) của một trường dữ liệu.
    private function getFieldName(string $field): string
    {
        return self::$fieldNames[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    // Kiểm tra xem quá trình xác thực có phát hiện lỗi nào không.
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    // Lấy danh sách tất cả các thông báo lỗi đã xảy ra.
    public function getErrors(): array
    {
        // Tối ưu hóa việc làm phẳng mảng đa chiều bằng splat operator (PHP >= 7.4)
        return empty($this->errors) ? [] : array_merge(...array_values($this->errors));
    }

    /**
     * @throws ValidationException
     */
    public function throw(): void
    {
        if ($this->fails()) {
            throw new ValidationException($this->getErrors(), $this->data);
        }
    }

    // --- CÁC QUY TẮC VALIDATION ---

    // Kiểm tra trường dữ liệu không được bỏ trống.
    protected function ruleRequired(string $field, $value): bool
    {
        return !empty(trim((string)$value));
    }

    // Kiểm tra định dạng địa chỉ email hợp lệ.
    protected function ruleEmail(string $field, $value): bool
    {
        return empty($value) || filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    // Kiểm tra định dạng số điện thoại Việt Nam (10 số, bắt đầu bằng 03, 05, 07, 08, 09).
    protected function rulePhone(string $field, $value): bool
    {
        return empty($value) || preg_match('/^(0[3|5|7|8|9])+([0-9]{8})$/', (string)$value);
    }

    // Kiểm tra độ dài tối thiểu của chuỗi.
    protected function ruleMin(string $field, $value, string $param): bool
    {
        return mb_strlen((string)$value) >= (int)$param;
    }

    // Kiểm tra độ dài tối đa của chuỗi.
    protected function ruleMax(string $field, $value, string $param): bool
    {
        return mb_strlen((string)$value) <= (int)$param;
    }

    // Kiểm tra giá trị có nằm trong danh sách các giá trị cho phép hay không.
    protected function ruleIn(string $field, $value, string $param): bool
    {
        return in_array($value, explode(',', $param));
    }

    // Kiểm tra giá trị có phải là định dạng số hay không.
    protected function ruleNumeric(string $field, $value): bool
    {
        return is_numeric($value);
    }

    // Kiểm tra giá trị có phải là số thực (float) hay không.
    protected function ruleFloat(string $field, $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }

    // Kiểm tra giá trị (số) có lớn hơn một mức cụ thể hay không.
    protected function ruleGt(string $field, $value, string $param): bool
    {
        return is_numeric($value) && (float)$value > (float)$param;
    }

    // Kiểm tra mật khẩu xác nhận có khớp với mật khẩu gốc hay không.
    protected function rulePassword_match(string $field, $value, string $param): bool
    {
        return $value === ($this->data[$param] ?? null);
    }

    // Kiểm tra giá trị đã tồn tại trong CSDL chưa (Hỗ trợ loại trừ ID hiện tại).
    protected function ruleUnique(string $field, $value, string $param): bool
    {
        [$table, $column, $excludeId] = array_pad(explode(',', $param), 3, null);
        $column = $column ?? $field;

        $db = Database::getInstance();
        $sql = "SELECT id FROM {$table} WHERE {$column} = ? LIMIT 1";
        $params = [$value];

        if ($excludeId && isset($this->data[$excludeId])) {
            $sql .= " AND id != ?";
            $params[] = $this->data[$excludeId];
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() === false;
    }

    // Kiểm tra thời gian phải lớn hơn thời gian hiện tại (có bù trừ 5 phút trễ form).
    protected function ruleAfter_now(string $field, $value): bool
    {
        if (empty($value)) return true; // Cho phép rỗng
        $scheduledTime = strtotime((string)$value);
        // Thêm bộ đệm 5 phút (300 giây) để bù trừ thời gian thao tác điền form
        return $scheduledTime !== false && $scheduledTime >= (time() - 300);
    }

    // Kiểm tra thời gian không được vượt quá 1 tuần kể từ hiện tại.
    protected function ruleBefore_one_week(string $field, $value): bool
    {
        if (empty($value)) return true; // Cho phép rỗng, rule 'required' sẽ kiểm tra rỗng sau
        $scheduledTime = strtotime((string)$value);
        return $scheduledTime !== false && $scheduledTime <= strtotime('+7 days');
    }
}

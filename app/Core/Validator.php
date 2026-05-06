<?php

namespace App\Core;

use App\Exceptions\ValidationException;

class Validator
{
    private array $data;
    private array $errors = [];
    private array $rules;
    private static array $messages = [
        'required' => 'Trường :field không được để trống.',
        'email' => 'Trường :field phải là một địa chỉ email hợp lệ.',
        'phone' => 'Trường :field không phải là số điện thoại Việt Nam hợp lệ.',
        'min' => 'Trường :field phải có ít nhất :rule ký tự.',
        'max' => 'Trường :field không được vượt quá :rule ký tự.',
        'in' => 'Trường :field được chọn không hợp lệ.',
        'unique' => 'Trường :field đã tồn tại trong hệ thống.',
        'numeric' => 'Trường :field phải là một số.',
        'float' => 'Trường :field phải là một số thực.',
        'gt' => 'Trường :field phải lớn hơn :rule.',
        'gte' => 'Trường :field phải lớn hơn hoặc bằng :rule.',
        'password_match' => 'Mật khẩu xác nhận không khớp.',
        'datetime' => 'Trường :field không phải là ngày giờ hợp lệ.',
        'after_now' => 'Trường :field phải là một thời điểm trong tương lai.',
        'before_one_week' => 'Trường :field không được hẹn quá 1 tuần kể từ hiện tại.',
    ];

    private static array $fieldNames = [
        'receiver_name' => 'Tên người nhận',
        'receiver_phone' => 'Số điện thoại người nhận',
        'pickup_address' => 'Địa chỉ lấy hàng',
        'delivery_address' => 'Địa chỉ giao hàng',
        'weight' => 'Khối lượng',
        'scheduled_at' => 'Thời gian hẹn lấy hàng',
        'note' => 'Ghi chú',
        'payment_method' => 'Phương thức thanh toán',
        'license_plate' => 'Biển số xe',
        'account' => 'Tài khoản',
        'password' => 'Mật khẩu',
        'password_confirm' => 'Xác nhận mật khẩu',
        'otp' => 'Mã xác thực OTP'
    ];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

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

    private function addError(string $field, string $rule, ?string $param = null): void
    {
        $message = self::$messages[$rule] ?? 'Lỗi xác thực không xác định.';
        $this->errors[$field][] = str_replace([':field', ':rule'], [$this->getFieldName($field), $param], $message);
    }

    private function getFieldName(string $field): string
    {
        return self::$fieldNames[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

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

    protected function ruleRequired(string $field, $value): bool
    {
        return !empty(trim((string)$value));
    }

    protected function ruleEmail(string $field, $value): bool
    {
        return empty($value) || filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    protected function rulePhone(string $field, $value): bool
    {
        return empty($value) || preg_match('/^(0[3|5|7|8|9])+([0-9]{8})$/', (string)$value);
    }

    protected function ruleMin(string $field, $value, string $param): bool
    {
        return mb_strlen((string)$value) >= (int)$param;
    }

    protected function ruleMax(string $field, $value, string $param): bool
    {
        return mb_strlen((string)$value) <= (int)$param;
    }

    protected function ruleIn(string $field, $value, string $param): bool
    {
        return in_array($value, explode(',', $param));
    }

    protected function ruleNumeric(string $field, $value): bool
    {
        return is_numeric($value);
    }

    protected function ruleFloat(string $field, $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }

    protected function ruleGt(string $field, $value, string $param): bool
    {
        return is_numeric($value) && (float)$value > (float)$param;
    }

    protected function rulePassword_match(string $field, $value, string $param): bool
    {
        return $value === ($this->data[$param] ?? null);
    }

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

    protected function ruleAfter_now(string $field, $value): bool
    {
        if (empty($value)) return true; // Cho phép rỗng
        $scheduledTime = strtotime((string)$value);
        // Thêm bộ đệm 5 phút (300 giây) để bù trừ thời gian thao tác điền form
        return $scheduledTime !== false && $scheduledTime >= (time() - 300);
    }

    protected function ruleBefore_one_week(string $field, $value): bool
    {
        if (empty($value)) return true; // Cho phép rỗng, rule 'required' sẽ kiểm tra rỗng sau
        $scheduledTime = strtotime((string)$value);
        return $scheduledTime !== false && $scheduledTime <= strtotime('+7 days');
    }
}
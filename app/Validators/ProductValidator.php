<?php
namespace App\Validators;

use App\Core\Request;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;

/**
 * Lấy dữ liệu sản phẩm từ body JSON và kiểm tra hợp lệ.
 *
 *   [$data, $error] = $validator->validate($request->json());
 *   if ($error !== '') { ...trả lỗi 422... }
 */
class ProductValidator
{
    private const NAME_MAX_LENGTH = 255;

    /** @var CategoryRepository */
    private $categories;

    // Cần CategoryRepository để kiểm tra danh mục được chọn có tồn tại không
    public function __construct(CategoryRepository $categories)
    {
        $this->categories = $categories;
    }

    /**
     * @return array{0: array, 1: string} [dữ liệu đã làm sạch, thông báo lỗi ('' = hợp lệ)]
     */
    public function validate(array $input): array
    {
        $data  = $this->normalize($input);
        $error = $this->firstError($data);
        return [$data, $error];
    }

    // Chỉ lấy các trường cần, ép kiểu, bỏ khoảng trắng thừa
    private function normalize(array $input): array
    {
        $categoryId = $input['category_id'] ?? null;
        return [
            'name'        => Request::stringField($input, 'name'),
            'price'       => is_scalar($input['price'] ?? null) ? $input['price'] : '',
            'description' => Request::stringField($input, 'description'),
            // '' / null / 0 = chưa phân loại
            'category_id' => is_numeric($categoryId) && (int)$categoryId > 0 ? (int)$categoryId : null,
        ];
    }

    // Lỗi đầu tiên tìm thấy, hợp lệ thì trả về ''
    private function firstError(array $data): string
    {
        if ($data['name'] === '' || !is_numeric($data['price']) || $data['price'] < 0) {
            return 'Vui lòng nhập tên và giá hợp lệ.';
        }
        // Đếm theo ký tự (tiếng Việt có dấu là nhiều byte), không dùng strlen
        if (preg_match('/^.{' . (self::NAME_MAX_LENGTH + 1) . ',}/su', $data['name'])) {
            return 'Tên sản phẩm tối đa ' . self::NAME_MAX_LENGTH . ' ký tự.';
        }
        if ($data['price'] > ProductRepository::MAX_PRICE) {
            return 'Giá tối đa là ' . number_format(ProductRepository::MAX_PRICE, 2) . ' VND.';
        }
        if ($data['category_id'] !== null && !$this->categories->exists($data['category_id'])) {
            return 'Danh mục không tồn tại.';
        }
        return '';
    }
}

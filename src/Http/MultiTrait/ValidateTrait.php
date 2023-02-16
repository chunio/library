<?php

declare(strict_types=1);

namespace Baichuan\Library\Http\MultiTrait;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Arr;
use Hyperf\Validation\Contract\ValidatorFactoryInterface as ValidationFactory;
use Hyperf\Validation\Request\FormRequest;
use ReflectionClass;

trait ValidateTrait
{
    /**
     * @Inject
     */
    protected ValidationFactory $validationFactory;

    /**
     * @param array $data 校驗數據
     * @param array $rules 校驗規則
     */
    protected function validate($data, array $rules, array $messages = [], array $customAttributes = []): array
    {
        return $this->validationFactory->make(
            Arr::wrap($data),
            $rules,
            $messages,
            $customAttributes,
        )->validate();
    }

    /**
     * 校驗（通過類名）
     * @param $formRequestClass
     * @param mixed $data
     * @throws \ReflectionException
     */
    protected function validateByFormRequest($data, $formRequestClass): array
    {
        /** @var FormRequest $formRequest */
        $formRequest = di($formRequestClass);
        $refClass = new ReflectionClass($formRequestClass); // 傳入對象或類名，得到ReflectionClass
        $refMeth = $refClass->getMethod('getRules'); // 得到ReflectionMethod
        $refMeth->setAccessible(true); // 設置爲可見，也就是可訪問
        $rules = $refMeth->invoke($formRequest); // 傳入對象來訪問這個方法
        return $this->validationFactory->make(
            Arr::wrap($data),
            $rules,
            $formRequest->messages(),
            $formRequest->attributes(),
        )->validate();
    }
}

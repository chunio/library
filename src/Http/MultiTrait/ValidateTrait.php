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
     * 校验
     *
     * @param array $data 校验数据
     * @param array $rules 校验规则
     *
     * @author adi
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
     * 校验（通过类名）
     *
     * @param $formRequestClass
     * @param mixed $data
     *
     * @throws \ReflectionException
     *
     * @author Mai Zhong Wen <yshxinjian@gmail.com>
     */
    protected function validateByFormRequest($data, $formRequestClass): array
    {
        /** @var FormRequest $formRequest */
        $formRequest = di($formRequestClass);

        $refClass = new ReflectionClass($formRequestClass); // 传入对象或类名，得到ReflectionClass对象
        $refMeth = $refClass->getMethod('getRules'); // 得到ReflectionMethod对象
        $refMeth->setAccessible(true); // 设置为可见，也就是可访问
        $rules = $refMeth->invoke($formRequest); // 传入对象来访问这个方法

        return $this->validationFactory->make(
            Arr::wrap($data),
            $rules,
            $formRequest->messages(),
            $formRequest->attributes(),
        )->validate();
    }

}

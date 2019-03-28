<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace BitBag\SyliusElasticsearchPlugin\Form\Type\ChoiceMapper;

use BitBag\SyliusElasticsearchPlugin\EntityRepository\ProductAttributeValueRepositoryInterface;
use BitBag\SyliusElasticsearchPlugin\Formatter\StringFormatterInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;

final class ProductAttributesMapper implements ProductAttributesMapperInterface
{
    /** @var ProductAttributeValueRepositoryInterface */
    private $productAttributeValueRepository;

    /** @var LocaleContextInterface */
    private $localeContext;

    /** @var StringFormatterInterface */
    private $stringFormatter;

    public function __construct(
        ProductAttributeValueRepositoryInterface $productAttributeValueRepository,
        LocaleContextInterface $localeContext,
        StringFormatterInterface $stringFormatter
    ) {
        $this->productAttributeValueRepository = $productAttributeValueRepository;
        $this->localeContext = $localeContext;
        $this->stringFormatter = $stringFormatter;
    }

    public function mapToChoices(ProductAttributeInterface $productAttribute): array
    {
        $configuration = $productAttribute->getConfiguration();

        /**
         * If we have choices configured, no need to query and group by product attribute values -
         * they are predefined by the choices array
         */
        if (isset($configuration['choices']) && is_array($configuration['choices'])
        ) {
            $choices = [];
            foreach ($configuration['choices'] as $singleValue => $val) {
                $choice = $this->stringFormatter->formatToLowercaseWithoutSpaces($singleValue);
                $label = $configuration['choices'][$singleValue][$this->localeContext->getLocaleCode()];
                $choices[$label] = $choice;
            }
            return $choices;
        }

        $attributeValues = $this->productAttributeValueRepository->getUniqueAttributeValues($productAttribute);

        $choices = [];
        array_walk($attributeValues, function (ProductAttributeValueInterface $productAttributeValue) use (&$choices): void {
            $product = $productAttributeValue->getProduct();

            if (!$product->isEnabled()) {
                unset($product);
                return;
            }

            $value = $productAttributeValue->getValue();
            $configuration = $productAttributeValue->getAttribute()->getConfiguration();

            if (is_array($value)
                && isset($configuration['choices'])
                && is_array($configuration['choices'])
            ) {
                foreach ($value as $singleValue) {
                    $choice = $this->stringFormatter->formatToLowercaseWithoutSpaces($singleValue);
                    $label = $configuration['choices'][$singleValue][$this->localeContext->getLocaleCode()];
                    $choices[$label] = $choice;
                }
            } else {
                $choice = is_string($value) ? $this->stringFormatter->formatToLowercaseWithoutSpaces($value) : $value;
                $choices[$value] = $choice;
            }
        });
        unset($attributeValues);

        return $choices;
    }
}

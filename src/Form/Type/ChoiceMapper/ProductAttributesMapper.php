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

use BitBag\SyliusElasticsearchPlugin\Formatter\StringFormatterInterface;
use Doctrine\DBAL\Query\QueryBuilder;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Product\Repository\ProductAttributeValueRepositoryInterface;

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
//        var_dump($productAttribute->getCode());
        if (isset($configuration['choices']) && is_array($configuration['choices'])
        ) {
            $choices = [];
//            var_dump('choices');
            foreach ($configuration['choices'] as $singleValue => $val) {
                $choice = $this->stringFormatter->formatToLowercaseWithoutSpaces($singleValue);
                $label = $configuration['choices'][$singleValue][$this->localeContext->getLocaleCode()];
                $choices[$label] = $choice;
            }
//            echo ' a ';
            return $choices;
        }

//        if ($productAttribute->getStorageType() === 'boolean') {
//            $choices['1'] = 1;
//            return $choices;
//        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->productAttributeValueRepository->createQueryBuilder('o');

        $attributeValues = $queryBuilder
            ->where('o.attribute = :attribute')
            ->groupBy('o.'.$productAttribute->getStorageType())
            ->setParameter(':attribute', $productAttribute)
            ->getQuery()
            ->getResult();
//        $attributeValues = $this->productAttributeValueRepository->findBy(['attribute' => $productAttribute]);
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

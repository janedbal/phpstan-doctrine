<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\Descriptors;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type as DbalType;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Doctrine\DefaultDescriptorRegistry;
use PHPStan\Type\Doctrine\DescriptorNotRegisteredException;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class ReflectionDescriptor implements DoctrineTypeDescriptor
{

	/** @var class-string<DbalType> */
	private $type;

	/** @var ReflectionProvider */
	private $reflectionProvider;

	/** @var Container */
	private $container;

	/**
	 * @param class-string<DbalType> $type
	 */
	public function __construct(
		string $type,
		ReflectionProvider $reflectionProvider,
		Container $container
	)
	{
		$this->type = $type;
		$this->reflectionProvider = $reflectionProvider;
		$this->container = $container;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getWritableToPropertyType(): Type
	{
		$method = $this->reflectionProvider->getClass($this->type)->getNativeMethod('convertToPHPValue');
		$type = ParametersAcceptorSelector::selectFromTypes([
			new MixedType(),
			new ObjectType(AbstractPlatform::class),
		], $method->getVariants(), false)->getReturnType();

		return TypeCombinator::removeNull($type);
	}

	public function getWritableToDatabaseType(): Type
	{
		$method = $this->reflectionProvider->getClass($this->type)->getNativeMethod('convertToDatabaseValue');
		$type = ParametersAcceptorSelector::selectFromTypes([
			new MixedType(),
			new ObjectType(AbstractPlatform::class),
		], $method->getVariants(), false)->getParameters()[0]->getType();

		return TypeCombinator::removeNull($type);
	}

	public function getDatabaseInternalType(): Type
	{
		$registry = $this->container->getByType(DefaultDescriptorRegistry::class);
		$parents = $this->reflectionProvider->getClass($this->type)->getParentClassesNames();

		foreach ($parents as $dbalTypeParentClass) {
			try {
				// this assumes that if somebody inherits from DecimalType,
				// the real database type remains decimal and we can reuse its descriptor
				return $registry->getByClassName($dbalTypeParentClass)->getDatabaseInternalType();

			} catch (DescriptorNotRegisteredException $e) {
				continue;
			}
		}

		return new MixedType();
	}

}

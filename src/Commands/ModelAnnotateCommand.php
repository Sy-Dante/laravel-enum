<?php

namespace BenSampo\Enum\Commands;

use BenSampo\Enum\Docblock\EnumPropertyType;
use BenSampo\Enum\Tests\Models\Example;
use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Zend\Code\Generator\DocBlock\Tag\PropertyTag;
use Zend\Code\Generator\DocBlock\Tag\TagInterface;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Reflection\DocBlockReflection;

class ModelAnnotateCommand extends AbstractAnnotationCommand
{
    const PARENT_CLASS = Model::class;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'enum:annotate-model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate annotations for models that have enums';

    /**
     * Apply annotations to a reflected class
     *
     * @param ReflectionClass $reflectionClass
     *
     * @return void
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function annotate(ReflectionClass $reflectionClass)
    {
        if (!$reflectionClass->hasMethod('hasEnumCast')) {
            return;
        }

        $casts = $reflectionClass->getDefaultProperties()['enumCasts'] ?? [];

        $docBlock = DocBlockGenerator::fromArray([]);

        if (strlen($reflectionClass->getDocComment()) !== 0) {
            $docBlock = DocBlockGenerator::fromReflection(new DocBlockReflection($reflectionClass));
        }

        $docBlock->setTags($this->getDocblockTags($docBlock, $casts));
        $this->updateClassDocblock($reflectionClass, $docBlock);
    }

    protected function getClassFinder(): Finder
    {
        $finder = new Finder();

        if (!$this->option('folder')) {
            return $finder->files()->in(app_path())->depth('==0')->name('*.php');
        }

        return $finder->files()->in($this->option('folder'))->name('*.php');
    }

    private function getDocblockTags(DocBlockGenerator $docBlock, array $casts): array
    {
        $existingTags = array_filter($docBlock->getTags(), function (TagInterface $tag) use ($casts) {
            return !$tag instanceof PropertyTag || !in_array($tag->getPropertyName(), array_keys($casts), true);
        });

        return collect($casts)
            ->map(function ($className, $propertyName) {
                return new PropertyTag($propertyName, [sprintf('\%s', $className), 'null']);
            })
            ->merge($existingTags)
            ->toArray();
    }
}

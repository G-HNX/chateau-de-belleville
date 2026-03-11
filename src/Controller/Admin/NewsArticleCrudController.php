<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\News\NewsArticle;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class NewsArticleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return NewsArticle::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Article')
            ->setEntityLabelInPlural('Actualités')
            ->setDefaultSort(['publishedAt' => 'DESC', 'createdAt' => 'DESC'])
            ->setSearchFields(['title', 'excerpt', 'content']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('isPublished')
            ->add(DateTimeFilter::new('publishedAt', 'Date de publication'))
            ->add(DateTimeFilter::new('createdAt', 'Créé le'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Titre');

        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('title')
            ->hideOnIndex();

        yield ImageField::new('coverImage', 'Image de couverture')
            ->setUploadDir('public/uploads/news')
            ->setBasePath('/uploads/news')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(Crud::PAGE_NEW === $pageName)
            ->setHelp('Format recommandé : 1200×600 px');

        yield TextareaField::new('excerpt', 'Extrait')
            ->setHelp('Court résumé affiché dans la liste des actualités.')
            ->setNumOfRows(3)
            ->hideOnIndex();

        yield TextEditorField::new('content', 'Contenu')
            ->hideOnIndex();

        yield BooleanField::new('isPublished', 'Publié');

        yield DateTimeField::new('publishedAt', 'Date de publication')
            ->setHelp('Laissez vide pour utiliser la date actuelle lors de la publication.')
            ->setRequired(false)
            ->hideOnIndex();

        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');

        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->hideOnForm()
            ->hideOnIndex()
            ->setFormat('dd/MM/yyyy HH:mm');
    }
}

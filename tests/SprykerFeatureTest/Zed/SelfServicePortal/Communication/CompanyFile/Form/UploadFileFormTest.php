<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Communication\CompanyFile\Form;

use Codeception\Test\Unit;
use org\bovigo\vfs\content\LargeFileContent;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use SprykerFeature\Zed\SelfServicePortal\Communication\CompanyFile\Form\UploadFileForm;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;
use SprykerFeatureTest\Zed\SelfServicePortal\SelfServicePortalCommunicationTester;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validation;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Communication
 * @group CompanyFile
 * @group Form
 * @group UploadFileFormTest
 * Add your own group annotations below this line
 */
class UploadFileFormTest extends Unit
{
    protected const string VIRTUAL_ROOT_DIRECTORY = 'root';

    protected const string PDF_CONTENT = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n";

    protected const string PLAIN_TEXT_CONTENT = 'This is a plain text document.';

    /**
     * @uses \SprykerFeature\Zed\SelfServicePortal\Communication\CompanyFile\Form\UploadFileForm::addUploadedFileField()
     */
    protected const string MESSAGE_INVALID_MIME_TYPE = 'Please upload valid documents (PDF) or images (JPEG, PNG)';

    protected SelfServicePortalCommunicationTester $tester;

    protected vfsStreamDirectory $virtualDirectory;

    protected function _before(): void
    {
        $this->virtualDirectory = vfsStream::setup(static::VIRTUAL_ROOT_DIRECTORY);
    }

    public function testGivenMultiplePdfFilesWithinTheSizeLimitWhenTheFormIsSubmittedThenTheFormIsValid(): void
    {
        // Arrange
        $uploadFileForm = $this->createUploadFileForm();
        $uploadedFiles = [
            $this->createUploadedFile('first.pdf', static::PDF_CONTENT),
            $this->createUploadedFile('second.pdf', static::PDF_CONTENT),
        ];

        // Act
        $uploadFileForm->submit([UploadFileForm::FIELD_FILE_UPLOAD => $uploadedFiles]);

        // Assert
        $this->assertTrue($uploadFileForm->isValid());
    }

    public function testGivenAFileWithANotAllowedMimeTypeWhenTheFormIsSubmittedThenTheMimeTypeViolationIsReported(): void
    {
        // Arrange
        $uploadFileForm = $this->createUploadFileForm();
        $uploadedFiles = [
            $this->createUploadedFile('allowed.pdf', static::PDF_CONTENT),
            $this->createUploadedFile('not-allowed.pdf', static::PLAIN_TEXT_CONTENT),
        ];

        // Act
        $uploadFileForm->submit([UploadFileForm::FIELD_FILE_UPLOAD => $uploadedFiles]);

        // Assert
        $this->assertFalse($uploadFileForm->isValid());
        $this->assertSame([static::MESSAGE_INVALID_MIME_TYPE], $this->getErrorMessageTemplates($uploadFileForm));
    }

    public function testGivenAFileLargerThanTheConfiguredMaximumSizeWhenTheFormIsSubmittedThenTheMaximumSizeViolationIsReported(): void
    {
        // Arrange
        $uploadFileForm = $this->createUploadFileForm();
        $maximumFileSizeInBytes = (new File(['maxSize' => (new SelfServicePortalConfig())->getCompanyFileMaxFileSize()]))->maxSize;
        $oversizedFile = vfsStream::newFile('oversized.pdf')
            ->withContent(new LargeFileContent($maximumFileSizeInBytes + 1))
            ->at($this->virtualDirectory);
        $uploadedFiles = [
            new UploadedFile($oversizedFile->url(), 'oversized.pdf', 'application/pdf', null, true),
        ];

        // Act
        $uploadFileForm->submit([UploadFileForm::FIELD_FILE_UPLOAD => $uploadedFiles]);

        // Assert
        $this->assertFalse($uploadFileForm->isValid());
        $this->assertSame([(new File())->maxSizeMessage], $this->getErrorMessageTemplates($uploadFileForm));
    }

    protected function createUploadFileForm(): FormInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory()
            ->create(UploadFileForm::class);
    }

    protected function createUploadedFile(string $fileName, string $content): UploadedFile
    {
        $virtualFile = vfsStream::newFile($fileName)
            ->withContent($content)
            ->at($this->virtualDirectory);

        return new UploadedFile($virtualFile->url(), $fileName, null, null, true);
    }

    /**
     * @return list<string>
     */
    protected function getErrorMessageTemplates(FormInterface $form): array
    {
        $messageTemplates = [];

        foreach ($form->getErrors(true) as $formError) {
            /** @var \Symfony\Component\Form\FormError $formError */
            $messageTemplates[] = $formError->getMessageTemplate();
        }

        return $messageTemplates;
    }
}

<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace spec\Sylius\RefundPlugin\Sender;

use PhpSpec\ObjectBehavior;
use Sylius\Component\Mailer\Sender\SenderInterface;
use Sylius\RefundPlugin\Entity\CreditMemoInterface;
use Sylius\RefundPlugin\File\FileManagerInterface;
use Sylius\RefundPlugin\Generator\CreditMemoPdfFileGeneratorInterface;
use Sylius\RefundPlugin\Model\CreditMemoPdf;
use Sylius\RefundPlugin\Sender\CreditMemoEmailSenderInterface;

final class CreditMemoEmailSenderSpec extends ObjectBehavior
{
    function let(
        CreditMemoPdfFileGeneratorInterface $creditMemoPdfFileGenerator,
        SenderInterface $sender,
        FileManagerInterface $fileManager
    ): void {
        $this->beConstructedWith($creditMemoPdfFileGenerator, $sender, $fileManager, true);
    }

    function it_implements_credit_memo_email_sender_interface(): void
    {
        $this->shouldImplement(CreditMemoEmailSenderInterface::class);
    }

    function it_sends_an_email_with_credit_memo_and_pdf_file_attachment_to_customer(
        CreditMemoPdfFileGeneratorInterface $creditMemoPdfFileGenerator,
        SenderInterface $sender,
        FileManagerInterface $fileManager,
        CreditMemoInterface $creditMemo
    ): void {
        $creditMemo->getId()->willReturn('7903c83a-4c5e-4bcf-81d8-9dc304c6a353');

        $creditMemoPdf = new CreditMemoPdf('credit-memo.pdf', 'I am credit memo number #2018/10/000444');
        $creditMemoPdfFileGenerator
            ->generate('7903c83a-4c5e-4bcf-81d8-9dc304c6a353')
            ->willReturn($creditMemoPdf)
        ;

        $fileManager->createWithContent('credit-memo.pdf', 'I am credit memo number #2018/10/000444')->shouldBeCalled();

        $fileManager->realPath('credit-memo.pdf')->willReturn('/base/directory/credit-memo.pdf');

        $sender
            ->send('units_refunded', ['john@example.com'], ['creditMemo' => $creditMemo], ['/base/directory/credit-memo.pdf'])
            ->shouldBeCalled()
        ;

        $fileManager->remove('credit-memo.pdf')->shouldBeCalled();

        $this->send($creditMemo, 'john@example.com');
    }

    function it_sends_an_email_with_credit_memo_to_customer_without_pdf_file_attachment_if_pdf_file_generator_is_disabled(
        CreditMemoPdfFileGeneratorInterface $creditMemoPdfFileGenerator,
        SenderInterface $sender,
        FileManagerInterface $fileManager,
        CreditMemoInterface $creditMemo
    ): void {
        $this->beConstructedWith($creditMemoPdfFileGenerator, $sender, $fileManager, false);

        $creditMemo->getId()->shouldNotBeCalled();
        $creditMemoPdfFileGenerator->generate('creditMemoId')->shouldNotBeCalled();

        $fileManager->createWithContent('credit-memo.pdf', 'content')->shouldNotBeCalled();
        $fileManager->realPath('credit-memo.pdf')->shouldNotBeCalled();

        $sender->send('units_refunded', ['john@example.com'], ['creditMemo' => $creditMemo])->shouldBeCalled();

        $fileManager->remove('credit-memo.pdf')->shouldNotBeCalled();

        $this->send($creditMemo, 'john@example.com');
    }
}

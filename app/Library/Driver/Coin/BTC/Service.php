<?php

namespace Driver\Coin\BTC;

use App\Constants\ErrorCode;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39Mnemonic;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use Driver\Coin\AbstractService;

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface as I;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Transaction\Factory\SignData;
use BitWasp\Bitcoin\Script\WitnessScript;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Address\SegwitAddress;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Address\AddressCreator;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Service extends AbstractService
{
    protected $passphrase = ''; //密码
    protected $eth;

    public function __construct($config)
    {
        if (!$config)
            return $this->error(ErrorCode::DATA_NOT_EXIST);
        $this->config = $config;
    }

    public function newAddress()
    {
        $random = new Random();
        // 生成随机数(initial entropy)
        $entropy = $random->bytes(Bip39Mnemonic::MIN_ENTROPY_BYTE_LEN);
        // Bip39
        $bip39 = MnemonicFactory::bip39();
        // 通过随机数生成助记词
        $mnemonic = $bip39->entropyToMnemonic($entropy);
        // echo "mnemonic: " . $mnemonic . PHP_EOL . PHP_EOL; // 助记词

        $seedGenerator = new Bip39SeedGenerator();
        // 通过助记词生成种子，传入可选加密串'hello'
        $seed = $seedGenerator->getSeed($mnemonic);
        // echo "seed: " . $seed->getHex() . PHP_EOL;
        $hdFactory = new HierarchicalKeyFactory();
        $master = $hdFactory->fromEntropy($seed);

        $hardened = $master->derivePath("44'/0'/0'/0/0");
        $wif = $hardened->getPrivateKey()->toWif();
        // echo 'WIF: ' . $wif . PHP_EOL;
        $address = new PayToPubKeyHashAddress($hardened->getPublicKey()->getPubKeyHash());
        // echo 'address: ' . $address->getAddress() . PHP_EOL;

        //array {"mnemonic":"","key":"","address":""}
        $data = [];
        $data['mnemonic'] = $mnemonic;
        $data['key'] = $wif;
        $data['address'] = $address->getAddress();
        return $this->_notify($data);
    }

    public function balance(string $address)
    {
        $this->error(ErrorCode::DATA_NOT_EXIST);
        return $this->_notify();
    }

    public function transfer(string $from, string $to, string $number)
    {

        $this->error(ErrorCode::DATA_NOT_EXIST);

        // 支付钱包的私钥（wif 格式）
        $wif = $from;
        // 支付钱包上一次交易的 id
        $txid = '4c7a031a31fe794e64ef5ca2714bdd9dd10ceae44650bce025952282bdeeda8b';
        // 收款钱包地址（p2pkh 格式）
        $address = $to;

        $privKeyFactory = new PrivateKeyFactory;
        $key = $privKeyFactory->fromWif($wif);

        $witnessScript = new WitnessScript(
            ScriptFactory::scriptPubKey()->payToPubKeyHash($key->getPubKeyHash())
        );

        $dest = new SegwitAddress(
            WitnessProgram::v0(
                (new AddressCreator())->fromString($address)->getHash()
            )
        );

        // UTXO
        $outpoint = new OutPoint(Buffer::hex($txid, 32), 0);
        // 这里一共将支出 100000 聪（0.00000001 BTC = 1 聪）
        $txOut = new TransactionOutput(100000, $witnessScript);

        // 收款人将收到 90000 聪，中间的差将作为矿工的手续费
        $builder = (new TxBuilder())
            ->spendOutPoint($outpoint)
            ->payToAddress(90000, $dest);

        // 签署交易
        $signer = new Signer($builder->get(), Bitcoin::getEcAdapter());
        $input = $signer->input(0, $txOut);
        $input->sign($key);

        $signed = $signer->get();

        // 需要进行广播的交易
        $broadcast = $signed->getBaseSerialization()->getHex();

        var_dump($broadcast);
        // // 我这里使用 blockchain.info 的接口进行广播，你可以使用你自己的结点，或者现成的 RPC 接口
        // $client = new Client;
        // try {
        //     $response = $client->request('POST', 'https://blockchain.info/pushtx', [
        //         'form_params' => [
        //             'tx' => $signed->getBaseSerialization()->getHex()
        //         ]
        //     ]);
        //     var_dump(json_decode($response->getBody(), true));
        // } catch (ClientException $e) {
        //     var_dump($e->getResponse());
        // }

        return $this->_notify();
    }

    public function blockNumber()
    {

        return $this->_notify();
    }

    public function transactionReceipt(string $txHash)
    {

        return $this->_notify();
    }

    public function receiptStatus(string $txHash)
    {


        return $this->_notify(true);
    }

    public function _return()
    {
    }
}

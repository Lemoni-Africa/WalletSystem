<?php


enum Providers : string
{
    case CHAKRA = 'Chakra';
    case CRUST = 'Crust';
    case NUMERO = 'Numero';
}

enum TransactionStatus : string
{
    case PENDING = 'Pending';
    case COMPLETED = 'Completed';
    case FAILED = 'Failed';
}

enum gender : string
{
    case MALE = 'Male';
    case FEMALE = 'Female';
}
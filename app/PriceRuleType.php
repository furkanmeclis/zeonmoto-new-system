<?php

namespace App;

enum PriceRuleType: string
{
    case Percentage = 'percentage';
    case Amount = 'amount';
}

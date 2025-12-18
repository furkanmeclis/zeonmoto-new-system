<?php

namespace App;

enum PriceRuleScope: string
{
    case Global = 'global';
    case Category = 'category';
    case Product = 'product';
}

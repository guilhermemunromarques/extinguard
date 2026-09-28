<?php

namespace App;

enum ExtinguisherStatus: string
{
    case Active = 'active';
    case Maintenance = 'maintenance';
    case Decommissioned = 'decommissioned';
}

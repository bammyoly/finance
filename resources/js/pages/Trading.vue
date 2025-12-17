<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import axios from 'axios'
import { Head } from '@inertiajs/vue3'
import { type BreadcrumbItem } from '@/types'
import AppLayout from '@/layouts/AppLayout.vue'

type Symbol = 'BTC' | 'ETH'
type Side = 'buy' | 'sell'

type Asset = { symbol: string; amount: string; locked_amount: string }
type Profile = { usd_balance: string; assets: Asset[] }

type Order = {
  id: number
  user_id: number
  symbol: string
  side: Side
  price: string
  amount: string
  status: number
  locked_usd: string | null
  created_at: string
}

const symbols: Symbol[] = ['BTC', 'ETH']

const selectedSymbol = ref<Symbol>('BTC')
const side = ref<Side>('buy')
const price = ref<string>('95000')
const amount = ref<string>('0.01')

const profile = ref<Profile | null>(null)
const orders = ref<Order[]>([])
const orderbookBuys = ref<Order[]>([])
const orderbookSells = ref<Order[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const success = ref<string | null>(null)

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Trading', href: '/trading' }]

// ---------- formatting helpers (removes long trailing zeros) ----------
function trimZeros(s: string) {
  if (!s) return '0'
  if (!s.includes('.')) return s
  return s.replace(/\.?0+$/, '')
}

function fmtNumber(v: string | number, decimals: number) {
  const n = typeof v === 'number' ? v : Number(v)
  if (Number.isNaN(n)) return '0'
  return trimZeros(n.toFixed(decimals))
}

function fmtUsd(v: string | number) {
  return fmtNumber(v, 2)
}

// BTC/ETH precision display
function fmtAsset(v: string | number) {
  return fmtNumber(v, 8) // show up to 8 decimals neatly
}

const usdPreview = computed(() => {
  const p = Number(price.value || 0)
  const a = Number(amount.value || 0)
  return fmtUsd(p * a)
})

function statusLabel(status: number) {
  return status === 1 ? 'Open' : status === 2 ? 'Filled' : 'Cancelled'
}

function canCancel(o: Order) {
  return o.status === 1
}

// ---------- API ----------
async function fetchProfile() {
  const { data } = await axios.get('/api/profile')
  profile.value = data
}

async function fetchMyOrders() {
  const { data } = await axios.get('/api/my-orders')
  orders.value = data
}

async function fetchOrderbook() {
  const { data } = await axios.get('/api/orders', { params: { symbol: selectedSymbol.value } })
  orderbookBuys.value = data.buys
  orderbookSells.value = data.sells
}

async function placeOrder() {
  loading.value = true
  error.value = null
  success.value = null
  try {
    await axios.post('/api/orders', {
      symbol: selectedSymbol.value,
      side: side.value,
      price: price.value,
      amount: amount.value,
    })
    success.value = `${side.value.toUpperCase()} order placed.`
    await Promise.all([fetchProfile(), fetchOrderbook(), fetchMyOrders()])
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Failed to place order.'
  } finally {
    loading.value = false
  }
}

async function cancelOrder(orderId: number) {
  loading.value = true
  error.value = null
  success.value = null
  try {
    await axios.post(`/api/orders/${orderId}/cancel`)
    success.value = 'Order cancelled.'
    await Promise.all([fetchProfile(), fetchOrderbook(), fetchMyOrders()])
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Failed to cancel order.'
  } finally {
    loading.value = false
  }
}

// ---------- Orderbook Section ----------
const maxRows = 10

const sellsTop = computed(() => {
  // show best sells first (lowest price), limited rows
  const arr = [...orderbookSells.value]
  arr.sort((a, b) => Number(a.price) - Number(b.price) || new Date(a.created_at).getTime() - new Date(b.created_at).getTime())
  return arr.slice(0, maxRows)
})

const buysBottom = computed(() => {
  // show best buys first (highest price), limited rows
  const arr = [...orderbookBuys.value]
  arr.sort((a, b) => Number(b.price) - Number(a.price) || new Date(a.created_at).getTime() - new Date(b.created_at).getTime())
  return arr.slice(0, maxRows)
})

// ---------- Realtime Push ----------
let channel: any = null

function startRealtime() {
  const meId = (window as any).__ME_ID__ as number | undefined
  if (!meId) return

  channel = (window as any).Echo.private(`user.${meId}`).listen('.OrderMatched', async (payload: any) => {
    await Promise.all([fetchProfile(), fetchOrderbook(), fetchMyOrders()])
    success.value = `Matched: ${fmtAsset(payload.trade.amount)} ${payload.trade.symbol} @ ${fmtUsd(payload.trade.price)}`
    setTimeout(() => (success.value = null), 4000)
  })
}

function stopRealtime() {
  const meId = (window as any).__ME_ID__ as number | undefined
  if (meId) {
    ;(window as any).Echo.leave(`private-user.${meId}`)
    ;(window as any).Echo.leave(`user.${meId}`)
  }
  channel = null
}

onMounted(async () => {
  await Promise.all([fetchProfile(), fetchOrderbook(), fetchMyOrders()])
  startRealtime()
})

onBeforeUnmount(() => stopRealtime())

watch(selectedSymbol, async () => {
  await Promise.all([fetchOrderbook(), fetchMyOrders()])
})
</script>

<template>
  <Head title="Trading" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Top bar: Symbol switch + balance -->
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
          <h1 class="text-2xl font-semibold">Trading</h1>

          <!-- Symbol tabs -->
          <div class="ml-2 inline-flex rounded-lg border border-neutral-800 bg-neutral-950 p-1">
            <button
              v-for="s in symbols"
              :key="s"
              class="px-3 py-1 text-sm rounded-md transition"
              :class="selectedSymbol === s ? 'bg-neutral-800 text-white' : 'text-neutral-400 hover:text-white'"
              @click="selectedSymbol = s"
            >
              {{ s }}/USD
            </button>
          </div>
        </div>

        <div v-if="profile" class="text-sm text-neutral-300">
          USD Balance:
          <span class="font-semibold text-white">{{ fmtUsd(profile.usd_balance) }}</span>
        </div>
      </div>

      <div v-if="error" class="p-3 rounded border border-red-400/40 bg-red-500/10 text-red-200">
        {{ error }}
      </div>
      <div v-if="success" class="p-3 rounded border border-emerald-400/40 bg-emerald-500/10 text-emerald-200">
        {{ success }}
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Limit Order: Buy and Sell tabs -->
        <div class="p-4 border border-neutral-800 rounded-xl bg-neutral-950/40 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="font-semibold text-white">Limit Order</h2>

            <div class="inline-flex rounded-lg border border-neutral-800 bg-neutral-950 p-1">
              <button
                class="px-3 py-1 text-sm rounded-md transition"
                :class="side === 'buy' ? 'bg-emerald-600/20 text-emerald-300 border border-emerald-500/30' : 'text-neutral-400 hover:text-white'"
                @click="side = 'buy'"
              >
                Buy
              </button>
              <button
                class="ml-1 px-3 py-1 text-sm rounded-md transition"
                :class="side === 'sell' ? 'bg-red-600/20 text-red-300 border border-red-500/30' : 'text-neutral-400 hover:text-white'"
                @click="side = 'sell'"
              >
                Sell
              </button>
            </div>
          </div>

          <div class="text-sm text-neutral-400">
            Symbol: <span class="text-white font-semibold">{{ selectedSymbol }}</span>
          </div>

          <div class="space-y-2">
            <label class="block text-sm text-neutral-300">Price (USD)</label>
            <input v-model="price" type="number" step="0.01" class="w-full rounded-lg border border-neutral-800 bg-neutral-950 p-2 text-white" />
          </div>

          <div class="space-y-2">
            <label class="block text-sm text-neutral-300">Amount ({{ selectedSymbol }})</label>
            <input v-model="amount" type="number" step="0.00000001" class="w-full rounded-lg border border-neutral-800 bg-neutral-950 p-2 text-white" />
          </div>

          <div class="text-sm text-neutral-400">
            USD Value (est):
            <span class="font-semibold text-white">{{ usdPreview }}</span>
            <div class="text-xs text-neutral-500">Fee applied at match time (1.5%).</div>
          </div>

          <button
            class="w-full rounded-lg p-2 font-medium disabled:opacity-50"
            :class="side === 'buy' ? 'bg-emerald-600 text-white hover:bg-emerald-500' : 'bg-red-600 text-white hover:bg-red-500'"
            :disabled="loading"
            @click="placeOrder"
          >
            {{ loading ? 'Processing...' : side === 'buy' ? 'Place Buy Order' : 'Place Sell Order' }}
          </button>
        </div>

        <!-- Wallet Section -->
        <div class="p-4 border border-neutral-800 rounded-xl bg-neutral-950/40 space-y-4">
          <h2 class="font-semibold text-white">Wallet</h2>

          <div v-if="profile" class="space-y-3">
            <div class="text-sm">
              <span class="text-neutral-400">USD:</span>
              <span class="font-semibold text-white ml-2">{{ fmtUsd(profile.usd_balance) }}</span>
            </div>

            <div class="pt-2">
              <div class="text-sm font-medium text-neutral-200 mb-2">Assets</div>
              <div class="space-y-2">
                <div v-for="a in profile.assets" :key="a.symbol" class="text-sm flex items-center justify-between rounded-lg border border-neutral-800 bg-neutral-950 p-2">
                  <div class="font-semibold text-white">{{ a.symbol }}</div>
                  <div class="text-right text-neutral-300">
                    <div>Available: <span class="text-white">{{ fmtAsset(a.amount) }}</span></div>
                    <div class="text-neutral-500">Locked: {{ fmtAsset(a.locked_amount) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-sm text-neutral-500">Loading...</div>
        </div>

        <!-- Orderbook: sells on top, buys bottom -->
        <div class="p-4 border border-neutral-800 rounded-xl bg-neutral-950/40 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="font-semibold text-white">Orderbook ({{ selectedSymbol }})</h2>
            <button class="border border-neutral-800 rounded-lg px-3 py-1 text-sm text-neutral-200 hover:bg-neutral-900" :disabled="loading" @click="fetchOrderbook">
              Refresh
            </button>
          </div>

          <div class="grid grid-cols-2 text-xs text-neutral-500 px-1">
            <div>Price (USD)</div>
            <div class="text-right">Amount ({{ selectedSymbol }})</div>
          </div>

          <div class="space-y-4">
            <!-- SELLs (top) -->
            <div>
              <div class="text-xs font-semibold text-red-300 mb-2">SELL</div>
              <div class="space-y-1">
                <div
                  v-for="o in sellsTop"
                  :key="o.id"
                  class="grid grid-cols-2 items-center rounded-md border border-neutral-800 bg-neutral-950 px-2 py-1"
                >
                  <div class="text-red-300 font-medium">{{ fmtUsd(o.price) }}</div>
                  <div class="text-right text-neutral-200">{{ fmtAsset(o.amount) }}</div>
                </div>
                <div v-if="sellsTop.length === 0" class="text-sm text-neutral-600">No sell orders</div>
              </div>
            </div>

            <!-- BUYs (bottom) -->
            <div>
              <div class="text-xs font-semibold text-emerald-300 mb-2">BUY</div>
              <div class="space-y-1">
                <div
                  v-for="o in buysBottom"
                  :key="o.id"
                  class="grid grid-cols-2 items-center rounded-md border border-neutral-800 bg-neutral-950 px-2 py-1"
                >
                  <div class="text-emerald-300 font-medium">{{ fmtUsd(o.price) }}</div>
                  <div class="text-right text-neutral-200">{{ fmtAsset(o.amount) }}</div>
                </div>
                <div v-if="buysBottom.length === 0" class="text-sm text-neutral-600">No buy orders</div>
              </div>
            </div>
          </div>
        </div>

        <!-- All Orders -->
        <div class="p-4 border border-neutral-800 rounded-xl bg-neutral-950/40 space-y-4 lg:col-span-3">
          <div class="flex items-center justify-between">
            <h2 class="font-semibold text-white">All Orders</h2>
            <button class="border border-neutral-800 rounded-lg px-3 py-1 text-sm text-neutral-200 hover:bg-neutral-900" :disabled="loading" @click="fetchMyOrders">
              Refresh
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="text-left text-neutral-500">
                <tr class="border-b border-neutral-800">
                  <th class="py-2 pr-3">Time</th>
                  <th class="py-2 pr-3">Symbol</th>
                  <th class="py-2 pr-3">Side</th>
                  <th class="py-2 pr-3">Price</th>
                  <th class="py-2 pr-3">Amount</th>
                  <th class="py-2 pr-3">Status</th>
                  <th class="py-2 pr-3"></th>
                </tr>
              </thead>

              <tbody class="text-neutral-200">
                <tr v-for="o in orders" :key="o.id" class="border-b border-neutral-900">
                  <td class="py-2 pr-3 text-neutral-400">{{ new Date(o.created_at).toLocaleString() }}</td>
                  <td class="py-2 pr-3 font-semibold text-white">{{ o.symbol }}</td>
                  <td class="py-2 pr-3" :class="o.side === 'buy' ? 'text-emerald-300' : 'text-red-300'">
                    {{ o.side.toUpperCase() }}
                  </td>
                  <td class="py-2 pr-3">{{ fmtUsd(o.price) }}</td>
                  <td class="py-2 pr-3">{{ fmtAsset(o.amount) }}</td>
                  <td class="py-2 pr-3">
                    <span
                      class="inline-flex items-center px-2 py-1 rounded border text-xs"
                      :class="
                        o.status === 1
                          ? 'border-blue-500/30 text-blue-200 bg-blue-500/10'
                          : o.status === 2
                            ? 'border-emerald-500/30 text-emerald-200 bg-emerald-500/10'
                            : 'border-neutral-700 text-neutral-300 bg-neutral-900'
                      "
                    >
                      {{ statusLabel(o.status) }}
                    </span>
                  </td>
                  <td class="py-2 pr-3 text-right">
                    <button
                      v-if="canCancel(o)"
                      class="border border-neutral-800 rounded-lg px-3 py-1 text-sm hover:bg-neutral-900"
                      :disabled="loading"
                      @click="cancelOrder(o.id)"
                    >
                      Cancel
                    </button>
                  </td>
                </tr>

                <tr v-if="orders.length === 0">
                  <td colspan="7" class="py-4 text-neutral-600">No orders yet.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
